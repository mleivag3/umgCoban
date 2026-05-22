#!/bin/bash
# Configura webhook + ngrok como servicios systemd
# Ejecutar: sudo bash setup.sh <DEPLOY_TOKEN> <NGROK_AUTHTOKEN>

set -e
DEPLOY_TOKEN="${1:?Falta DEPLOY_TOKEN como argumento 1}"
NGROK_TOKEN="${2:?Falta NGROK_AUTHTOKEN como argumento 2}"
COMPOSE_DIR="/home/marco/php-cicd-demo"
USER="marco"

echo "==> Configurando ngrok authtoken..."
/snap/bin/ngrok config add-authtoken "$NGROK_TOKEN"

echo "==> Creando servicio systemd: deploy-webhook"
cat > /etc/systemd/system/deploy-webhook.service <<EOF
[Unit]
Description=Deploy Webhook Server
After=network.target docker.service
Wants=docker.service

[Service]
Type=simple
User=${USER}
WorkingDirectory=${COMPOSE_DIR}
Environment=DEPLOY_TOKEN=${DEPLOY_TOKEN}
Environment=COMPOSE_DIR=${COMPOSE_DIR}
Environment=WEBHOOK_PORT=9000
ExecStart=/usr/bin/python3 ${COMPOSE_DIR}/deploy/webhook.py
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

echo "==> Creando servicio systemd: ngrok-tunnel"
cat > /etc/systemd/system/ngrok-tunnel.service <<EOF
[Unit]
Description=ngrok Tunnel (webhook deploy)
After=network.target deploy-webhook.service
Wants=deploy-webhook.service

[Service]
Type=simple
User=${USER}
ExecStart=/snap/bin/ngrok http 9000 --log=stdout
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

echo "==> Habilitando y arrancando servicios..."
systemctl daemon-reload
systemctl enable deploy-webhook ngrok-tunnel
systemctl restart deploy-webhook ngrok-tunnel

sleep 3
echo "==> Estado de servicios:"
systemctl is-active deploy-webhook && echo "  deploy-webhook: OK" || echo "  deploy-webhook: FALLO"
systemctl is-active ngrok-tunnel   && echo "  ngrok-tunnel:   OK" || echo "  ngrok-tunnel:   FALLO"

echo ""
echo "==> URL publica de ngrok (espera 5s):"
sleep 5
curl -s http://localhost:4040/api/tunnels | python3 -c "
import json,sys
data=json.load(sys.stdin)
tunnels=data.get('tunnels',[])
for t in tunnels:
    print('  DEPLOY_WEBHOOK_URL =', t['public_url'] + '/deploy')
" || echo "  Abre http://localhost:4040 para ver la URL"

echo ""
echo "==> SIGUIENTE PASO: Agrega estos secrets en GitHub:"
echo "   DEPLOY_WEBHOOK_URL = <URL de arriba>/deploy"
echo "   DEPLOY_TOKEN       = ${DEPLOY_TOKEN}"
