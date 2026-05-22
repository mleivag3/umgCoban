<?php
require_once 'config.php';
require_login();
$user = current_user();

$stats = [
    ['label' => 'Deploys hoy',      'value' => '12',   'delta' => '+3',  'color' => '#3b82f6', 'icon' => '&#128640;'],
    ['label' => 'Tests pasados',    'value' => '248',  'delta' => '+18', 'color' => '#10b981', 'icon' => '&#9989;'],
    ['label' => 'Builds exitosos',  'value' => '97%',  'delta' => '+2%', 'color' => '#8b5cf6', 'icon' => '&#127959;'],
    ['label' => 'Tiempo promedio',  'value' => '1m42s','delta' => '-8s', 'color' => '#f59e0b', 'icon' => '&#9201;'],
];

$pipelines = [
    ['repo' => 'php-cicd-demo', 'branch' => 'main',    'status' => 'success', 'ago' => 'hace 2 min',  'duration' => '1m 38s'],
    ['repo' => 'php-cicd-demo', 'branch' => 'develop', 'status' => 'running', 'ago' => 'hace 5 min',  'duration' => '...'],
    ['repo' => 'api-service',   'branch' => 'main',    'status' => 'success', 'ago' => 'hace 12 min', 'duration' => '2m 05s'],
    ['repo' => 'frontend-app',  'branch' => 'feature', 'status' => 'failed',  'ago' => 'hace 20 min', 'duration' => '0m 47s'],
    ['repo' => 'worker-jobs',   'branch' => 'main',    'status' => 'success', 'ago' => 'hace 1 h',    'duration' => '3m 12s'],
];

$activity = [
    ['user' => 'Marco',   'action' => 'Push a main',            'repo' => 'php-cicd-demo', 'ago' => '2 min'],
    ['user' => 'Sistema', 'action' => 'Deploy a produccion',    'repo' => 'php-cicd-demo', 'ago' => '3 min'],
    ['user' => 'Marco',   'action' => 'Abrio Pull Request #14', 'repo' => 'api-service',   'ago' => '15 min'],
    ['user' => 'Sistema', 'action' => 'Tests fallaron',         'repo' => 'frontend-app',  'ago' => '20 min'],
    ['user' => 'Marco',   'action' => 'Merge a develop',        'repo' => 'worker-jobs',   'ago' => '1 h'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard — <?= APP_NAME ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #1e293b; }

    /* SIDEBAR */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0; width: 240px;
      background: #0f172a; display: flex; flex-direction: column;
      padding: 0; z-index: 100;
    }
    .sidebar-logo {
      padding: 24px 20px 20px;
      border-bottom: 1px solid #1e3a5f;
      display: flex; align-items: center; gap: 10px;
    }
    .sidebar-logo .icon {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg,#3b82f6,#1d4ed8);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; flex-shrink: 0;
    }
    .sidebar-logo span { font-size: 16px; font-weight: 700; color: #f1f5f9; }
    .sidebar nav { padding: 16px 12px; flex: 1; }
    .nav-label { font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 1px; padding: 8px 8px 4px; }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 12px; border-radius: 8px; cursor: pointer;
      font-size: 14px; color: #94a3b8; margin-bottom: 2px;
      text-decoration: none; transition: background .15s, color .15s;
    }
    .nav-item:hover { background: #1e293b; color: #e2e8f0; }
    .nav-item.active { background: #1e40af; color: #fff; }
    .sidebar-bottom {
      padding: 16px; border-top: 1px solid #1e3a5f;
      display: flex; align-items: center; gap: 10px;
    }
    .avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg,#3b82f6,#8b5cf6);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; color: #fff; font-size: 14px; flex-shrink: 0;
    }
    .user-info { flex: 1; min-width: 0; }
    .user-info .name { font-size: 13px; font-weight: 600; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-info .role { font-size: 11px; color: #64748b; }
    .logout-btn {
      color: #64748b; text-decoration: none; font-size: 18px;
      transition: color .15s;
    }
    .logout-btn:hover { color: #ef4444; }

    /* MAIN */
    .main { margin-left: 240px; min-height: 100vh; }
    .topbar {
      background: #fff; border-bottom: 1px solid #e2e8f0;
      padding: 16px 28px; display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .topbar h2 { font-size: 18px; font-weight: 700; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .build-info { font-size: 11px; color: #94a3b8; }

    .content { padding: 28px; }

    /* STATS */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
    .stat-card {
      background: #fff; border-radius: 14px; padding: 22px;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
      display: flex; flex-direction: column; gap: 8px;
    }
    .stat-icon { font-size: 22px; }
    .stat-label { font-size: 12px; color: #64748b; font-weight: 500; }
    .stat-value { font-size: 28px; font-weight: 800; }
    .stat-delta { font-size: 12px; color: #10b981; font-weight: 600; }

    /* PIPELINE CHART (fake bars) */
    .chart-card {
      background: #fff; border-radius: 14px; padding: 22px;
      box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 28px;
    }
    .chart-title { font-size: 14px; font-weight: 700; margin-bottom: 18px; }
    .bars { display: flex; align-items: flex-end; gap: 10px; height: 100px; }
    .bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .bar {
      width: 100%; border-radius: 6px 6px 0 0;
      background: linear-gradient(180deg,#3b82f6,#1d4ed8);
      transition: opacity .2s;
    }
    .bar:hover { opacity: .8; }
    .bar-label { font-size: 10px; color: #94a3b8; }

    /* TWO COLS */
    .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .panel { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    .panel-title { font-size: 14px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .panel-title span { font-size: 11px; font-weight: 400; color: #94a3b8; }

    /* PIPELINES TABLE */
    .pipeline-row {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 0; border-bottom: 1px solid #f1f5f9;
      font-size: 13px;
    }
    .pipeline-row:last-child { border-bottom: none; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .status-dot.success { background: #10b981; }
    .status-dot.running { background: #f59e0b; animation: pulse 1s infinite; }
    .status-dot.failed  { background: #ef4444; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .pipeline-name { flex: 1; font-weight: 600; }
    .pipeline-branch { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 7px; border-radius: 4px; }
    .pipeline-meta { font-size: 11px; color: #94a3b8; text-align: right; min-width: 70px; }

    /* ACTIVITY */
    .activity-row { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .activity-row:last-child { border-bottom: none; }
    .act-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; flex-shrink: 0; margin-top: 5px; }
    .act-body { flex: 1; }
    .act-user { font-weight: 600; }
    .act-action { color: #475569; }
    .act-repo { font-size: 11px; color: #94a3b8; }
    .act-ago { font-size: 11px; color: #94a3b8; white-space: nowrap; }

    .footer { text-align: center; padding: 20px; font-size: 11px; color: #94a3b8; }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="icon">&#128640;</div>
    <span><?= APP_NAME ?></span>
  </div>
  <nav>
    <div class="nav-label">Menu</div>
    <a class="nav-item active" href="#">&#128202; Dashboard</a>
    <a class="nav-item" href="#">&#9881; Pipelines</a>
    <a class="nav-item" href="#">&#127959; Builds</a>
    <a class="nav-item" href="#">&#128202; Metricas</a>
    <div class="nav-label" style="margin-top:12px">Sistema</div>
    <a class="nav-item" href="#">&#128196; Logs</a>
    <a class="nav-item" href="#">&#128100; Usuarios</a>
    <a class="nav-item" href="#">&#9881; Configuracion</a>
  </nav>
  <div class="sidebar-bottom">
    <div class="avatar"><?= $user['avatar'] ?></div>
    <div class="user-info">
      <div class="name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="role"><?= htmlspecialchars($user['role']) ?></div>
    </div>
    <a href="logout.php" class="logout-btn" title="Cerrar sesion">&#10005;</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <h2>&#128202; Dashboard</h2>
    <div class="topbar-right">
      <span class="badge badge-blue">&#128994; Sistema OK</span>
      <span class="build-info">v<?= APP_VERSION ?> &mdash; <?= BUILD_DATE ?></span>
    </div>
  </div>

  <div class="content">

    <!-- STATS -->
    <div class="stats-grid">
      <?php foreach ($stats as $s): ?>
      <div class="stat-card">
        <div class="stat-icon"><?= $s['icon'] ?></div>
        <div class="stat-label"><?= $s['label'] ?></div>
        <div class="stat-value" style="color:<?= $s['color'] ?>"><?= $s['value'] ?></div>
        <div class="stat-delta">&#8593; <?= $s['delta'] ?> vs ayer</div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- FAKE BAR CHART -->
    <div class="chart-card">
      <div class="chart-title">Deploys por dia (ultimos 7 dias)</div>
      <div class="bars">
        <?php
        $days   = ['Lun','Mar','Mie','Jue','Vie','Sab','Dom'];
        $vals   = [45, 72, 60, 88, 95, 40, 78];
        $max    = max($vals);
        foreach ($days as $i => $d):
            $h = round(($vals[$i] / $max) * 90);
        ?>
        <div class="bar-wrap">
          <div class="bar" style="height:<?= $h ?>px" title="<?= $vals[$i] ?> deploys"></div>
          <div class="bar-label"><?= $d ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TWO COLS -->
    <div class="two-cols">

      <!-- PIPELINES -->
      <div class="panel">
        <div class="panel-title">Pipelines recientes <span>ultimas 24h</span></div>
        <?php foreach ($pipelines as $p): ?>
        <div class="pipeline-row">
          <div class="status-dot <?= $p['status'] ?>"></div>
          <div class="pipeline-name"><?= $p['repo'] ?></div>
          <div class="pipeline-branch"><?= $p['branch'] ?></div>
          <div class="pipeline-meta">
            <?= $p['duration'] ?><br><?= $p['ago'] ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ACTIVITY -->
      <div class="panel">
        <div class="panel-title">Actividad reciente <span>en vivo</span></div>
        <?php foreach ($activity as $a): ?>
        <div class="activity-row">
          <div class="act-dot"></div>
          <div class="act-body">
            <span class="act-user"><?= $a['user'] ?></span>
            <span class="act-action"> &mdash; <?= $a['action'] ?></span>
            <div class="act-repo">&#128193; <?= $a['repo'] ?></div>
          </div>
          <div class="act-ago"><?= $a['ago'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /two-cols -->

  </div><!-- /content -->

  <div class="footer">
    <?= APP_NAME ?> v<?= APP_VERSION ?> &mdash; build <?= BUILD_DATE ?> &mdash; Logged in as <strong><?= htmlspecialchars($user['name']) ?></strong>
  </div>
</main>

</body>
</html>
