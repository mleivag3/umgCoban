#!/usr/bin/env python3
"""
Webhook server que escucha llamadas de GitHub Actions y ejecuta el deploy.
Puerto: 9000
"""
import os, json, subprocess, logging
from http.server import HTTPServer, BaseHTTPRequestHandler

DEPLOY_TOKEN   = os.environ.get('DEPLOY_TOKEN', 'changeme-token-secreto')
COMPOSE_DIR    = os.environ.get('COMPOSE_DIR', '/home/marco/php-cicd-demo')
LOG_FILE       = os.path.join(COMPOSE_DIR, 'deploy', 'webhook.log')

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s %(levelname)s %(message)s',
    handlers=[logging.StreamHandler(), logging.FileHandler(LOG_FILE, 'a')]
)
log = logging.getLogger('webhook')


def run_deploy(payload: dict) -> tuple[bool, str]:
    image  = payload.get('image', '')
    sha    = payload.get('sha', 'unknown')
    actor  = payload.get('actor', 'unknown')
    log.info(f"Deploy iniciado: sha={sha} actor={actor} image={image}")
    try:
        env = os.environ.copy()
        env['GITHUB_REPOSITORY'] = image.replace('ghcr.io/', '').split(':')[0]
        result = subprocess.run(
            ['docker', 'compose', '-f', 'docker-compose.yml', 'pull'],
            cwd=COMPOSE_DIR, capture_output=True, text=True, timeout=120, env=env
        )
        log.info(f"Pull: {result.stdout[-500:]} {result.stderr[-200:]}")

        result2 = subprocess.run(
            ['docker', 'compose', '-f', 'docker-compose.yml', 'up', '-d', '--remove-orphans'],
            cwd=COMPOSE_DIR, capture_output=True, text=True, timeout=60, env=env
        )
        log.info(f"Up: {result2.stdout[-500:]} {result2.stderr[-200:]}")

        if result.returncode == 0 and result2.returncode == 0:
            log.info("Deploy exitoso.")
            return True, "Deploy exitoso"
        return False, result2.stderr[-300:]
    except Exception as e:
        log.error(f"Error en deploy: {e}")
        return False, str(e)


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        log.info(f"{self.address_string()} {fmt % args}")

    def send_json(self, code: int, body: dict):
        data = json.dumps(body).encode()
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_GET(self):
        if self.path == '/health':
            self.send_json(200, {'status': 'ok'})
        else:
            self.send_json(404, {'error': 'not found'})

    def do_POST(self):
        if self.path != '/deploy':
            self.send_json(404, {'error': 'not found'}); return

        token = self.headers.get('X-Deploy-Token', '')
        if token != DEPLOY_TOKEN:
            log.warning(f"Token invalido desde {self.client_address}")
            self.send_json(401, {'error': 'unauthorized'}); return

        length  = int(self.headers.get('Content-Length', 0))
        payload = json.loads(self.rfile.read(length) or b'{}')

        ok, msg = run_deploy(payload)
        self.send_json(200 if ok else 500, {'ok': ok, 'msg': msg})


if __name__ == '__main__':
    port = int(os.environ.get('WEBHOOK_PORT', 9000))
    log.info(f"Webhook server escuchando en :{port}")
    HTTPServer(('0.0.0.0', port), Handler).serve_forever()
