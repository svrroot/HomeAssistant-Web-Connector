<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Login-Check
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Config-Datei laden
$config_file = 'sensor_config.json';
$config = [];

if(file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
} else {
    $config = ['sensors' => [], 'category_order' => []];
}

// Import Verarbeitung
if(isset($_POST['import_config'])) {
    $import_data = trim($_POST['import_json']);
    if(!empty($import_data)) {
        $imported_config = json_decode($import_data, true);
        if($imported_config && json_last_error() === JSON_ERROR_NONE) {
            // Validierung der importierten Daten
            if(isset($imported_config['sensors']) && is_array($imported_config['sensors'])) {
                file_put_contents($config_file, json_encode($imported_config, JSON_PRETTY_PRINT));
                $config = $imported_config;
                $success = "✅ Konfiguration erfolgreich importiert! (" . count($imported_config['sensors']) . " Sensoren)";
            } else {
                $error = "❌ Ungültiges JSON-Format! 'sensors' Array fehlt.";
            }
        } else {
            $error = "❌ Ungültiges JSON-Format! Bitte überprüfen Sie die Syntax.";
        }
    } else {
        $error = "❌ Bitte JSON-Daten eingeben!";
    }
}

// Backup erstellen
if(isset($_POST['create_backup'])) {
    $backup_name = 'sensor_config_backup_' . date('Y-m-d_H-i-s') . '.json';
    $backup_content = json_encode($config, JSON_PRETTY_PRINT);
    
    // Als Download bereitstellen
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $backup_name . '"');
    echo $backup_content;
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export - Sensor Config</title>
    <style>
        :root {
            --bg-color: #f5f5f5;
            --card-bg: white;
            --text-color: #333;
            --input-bg: white;
            --input-border: #ddd;
            --code-bg: #f8f9fa;
            --code-border: #e9ecef;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-color: #e0e0e0;
            --input-bg: #3d3d3d;
            --input-border: #555;
            --code-bg: #1e1e1e;
            --code-border: #444;
        }

        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: var(--bg-color); 
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { 
            background: var(--card-bg); 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
        }
        .header-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .section { 
            background: var(--card-bg); 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        textarea { 
            width: 100%; 
            min-height: 300px; 
            padding: 15px; 
            border: 1px solid var(--input-border); 
            border-radius: 5px; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            box-sizing: border-box;
            background: var(--input-bg);
            color: var(--text-color);
            resize: vertical;
        }
        .code-block { 
            background: var(--code-bg); 
            border: 1px solid var(--code-border); 
            padding: 15px; 
            border-radius: 5px; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            white-space: pre-wrap; 
            max-height: 400px; 
            overflow-y: auto; 
            position: relative;
        }
        button { 
            background: #28a745; 
            color: white; 
            padding: 10px 15px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 5px; 
        }
        button:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-purple { background: #6f42c1; }
        .btn-purple:hover { background: #5a359a; }
        .success { 
            color: #155724; 
            background: #d4edda; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
            border: 1px solid #c3e6cb; 
        }
        .error { 
            color: #721c24; 
            background: #f8d7da; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
            border: 1px solid #f5c6cb; 
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .stat-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
        }
        .stat-number { font-size: 2em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.8; }
        .copy-btn { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            border-radius: 3px; 
            cursor: pointer; 
            font-size: 12px; 
        }
        .copy-btn:hover { background: #0056b3; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .button-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; }
            .header-buttons { justify-content: center; }
            .two-column { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr; }
        }

        [data-theme="dark"] .success {
            color: #d1e7dd;
            background: #0f5132;
            border-color: #146c43;
        }
        
        [data-theme="dark"] .error {
            color: #f8d7da;
            background: #842029;
            border-color: #a02834;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Import/Export - Sensor Konfiguration</h1>
<!-- Im Header-Bereich, diese Zeilen ändern: -->
<div class="header-buttons">
    <a href="admin.php"><button class="btn-secondary"> Admin Panel</button></a>
    <a href="index.php"><button class="btn-info"> Dashboard</button></a>
    <button class="btn-purple" onclick="toggleTheme()"> Dark Mode</button>
</div>

        </div>

        <?php if(isset($success)) echo '<div class="success">' . $success . '</div>'; ?>
        <?php if(isset($error)) echo '<div class="error">' . $error . '</div>'; ?>

        <!-- Statistiken -->
        <div class="section">
            <h2> Aktuelle Konfiguration</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($config['sensors'] ?? []); ?></div>
                    <div class="stat-label">Sensoren</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($config['category_order'] ?? []); ?></div>
                    <div class="stat-label">Kategorien</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo file_exists($config_file) ? round(filesize($config_file) / 1024, 1) : 0; ?> KB</div>
                    <div class="stat-label">Dateigröße</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo file_exists($config_file) ? date('d.m.Y H:i', filemtime($config_file)) : 'Nie'; ?></div>
                    <div class="stat-label">Letzte Änderung</div>
                </div>
            </div>
        </div>

        <div class="two-column">
            <!-- Export Sektion -->
            <div class="section">
                <h2> Export</h2>
                <p>Aktuelle Konfiguration exportieren:</p>
                
                <div class="form-group">
                    <div class="button-group">
                        <button onclick="copyToClipboard('configData')" class="btn-primary"> In Zwischenablage kopieren</button>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="create_backup" class="btn-warning"> Als Datei herunterladen</button>
                        </form>
                    </div>
                </div>

                <div style="position: relative;">
                    <button class="copy-btn" onclick="copyToClipboard('configData')"> Kopieren</button>
                    <pre class="code-block" id="configData"><?php echo htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
            </div>

            <!-- Import Sektion -->
            <div class="section">
                <h2> Import</h2>
                <p>⚠️ <strong>Achtung:</strong> Der Import überschreibt die komplette aktuelle Konfiguration!</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>JSON-Konfiguration einfügen:</label>
                        <textarea name="import_json" placeholder="Hier die komplette JSON-Konfiguration einfügen..." required></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" onclick="validateJson()" class="btn-info"> JSON validieren</button>
                        <button type="submit" name="import_config" class="btn-danger" onclick="return confirm('⚠️ WARNUNG: Dies überschreibt die komplette aktuelle Konfiguration!\n\nSind Sie sicher?')">
                             Konfiguration importieren
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kategorie-Übersicht -->
        <?php if(!empty($config['category_order'])): ?>
        <div class="section">
            <h2> Kategorien (<?php echo count($config['category_order']); ?>)</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach($config['category_order'] as $category): ?>
                    <?php 
                    $category_count = 0;
                    foreach($config['sensors'] as $sensor) {
                        if($sensor['category'] === $category) $category_count++;
                    }
                    ?>
                    <span style="background: #e9ecef; padding: 8px 12px; border-radius: 15px; font-size: 14px;">
                         <?php echo ucfirst(htmlspecialchars($category)); ?> (<?php echo $category_count; ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sensor-Übersicht -->
        <?php if(!empty($config['sensors'])): ?>
        <div class="section">
            <h2> Sensor-Übersicht (<?php echo count($config['sensors']); ?>)</h2>
            <div style="max-height: 300px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background: var(--code-bg); position: sticky; top: 0;">
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--input-border);">Entity ID</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--input-border);">Beschreibung</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--input-border);">Kategorie</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--input-border);">Einheit</th>
                    </tr>
                    <?php foreach($config['sensors'] as $entity_id => $sensor): ?>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid var(--input-border); font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($entity_id); ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid var(--input-border);"><?php echo htmlspecialchars($sensor['description']); ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid var(--input-border);">
                            <span style="background: #007bff; color: white; padding: 2px 6px; border-radius: 10px; font-size: 12px;">
                                <?php echo ucfirst(htmlspecialchars($sensor['category'])); ?>
                            </span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid var(--input-border);"><?php echo htmlspecialchars($sensor['unit'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Copy to Clipboard
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('✅ Konfiguration in Zwischenablage kopiert!');
                });
            } else {
                // Fallback für ältere Browser
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('✅ Konfiguration in Zwischenablage kopiert!');
            }
        }

        // JSON Validierung
        function validateJson() {
            const textarea = document.querySelector('textarea[name="import_json"]');
            const jsonText = textarea.value.trim();
            
            if (!jsonText) {
                alert('❌ Bitte JSON-Daten eingeben!');
                return;
            }
            
            try {
                const parsed = JSON.parse(jsonText);
                
                // Struktur prüfen
                if (!parsed.sensors || typeof parsed.sensors !== 'object') {
                    alert('❌ Ungültige Struktur: "sensors" Objekt fehlt!');
                    return;
                }
                
                const sensorCount = Object.keys(parsed.sensors).length;
                const categoryCount = parsed.category_order ? parsed.category_order.length : 0;
                
                alert(`✅ JSON ist gültig!\n\n Sensoren: ${sensorCount}\n Kategorien: ${categoryCount}\n\nSie können die Konfiguration jetzt importieren.`);
                
            } catch (error) {
                alert(`❌ Ungültiges JSON!\n\nFehler: ${error.message}`);
            }
        }

        // Dark Mode Toggle
        function toggleTheme() {
            const body = document.body;
            const button = document.querySelector('.btn-purple');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                button.innerHTML = ' Dark Mode';
                localStorage.setItem('export_theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                button.innerHTML = '☀️ Light Mode';
                localStorage.setItem('export_theme', 'dark');
            }
        }

        // Theme beim Laden setzen
        if (localStorage.getItem('export_theme') === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.querySelector('.btn-purple').innerHTML = '☀️ Light Mode';
        }
    </script>
</body>
</html>
