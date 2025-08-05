<?php
/*
 * ========================================
 * SENSOR DASHBOARD - ADMIN PANEL
 * ========================================
 * 
 
 
 
 * Features:
 * ✅ Sensor-Verwaltung (hinzufügen, bearbeiten, löschen)
 * ✅ Kategorie-Verwaltung und Sortierung
 * ✅ Drag & Drop Sensor-Sortierung
 * ✅ Inline-Bearbeitung im Sortier-Bereich
 * ✅ Auto-Save Funktionalität
 * ========================================
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

// Admin-Passwort
$admin_password = "yourAdminPassword";

// Login verarbeiten
if(isset($_POST['login'])) {
    if($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = "Falsches Passwort!";
    }
}

// Logout verarbeiten
if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Konfigurationsdatei verwalten
$config_file = 'sensor_config.json';
if(!file_exists($config_file)) {
    $initial_config = [
        'sensors' => [],
        'category_order' => ['energie', 'wetter', 'fahrzeug', 'temperatur', 'sicherheit', 'beleuchtung', 'heizung', 'wasser', 'netzwerk', 'multimedia', 'sensoren'],
        'sensor_order' => []
    ];
    file_put_contents($config_file, json_encode($initial_config, JSON_PRETTY_PRINT));
}

$config = json_decode(file_get_contents($config_file), true);
if (!isset($config['category_order'])) {
    $config['category_order'] = ['energie', 'wetter', 'fahrzeug', 'temperatur', 'sicherheit', 'beleuchtung', 'heizung', 'wasser', 'netzwerk', 'multimedia', 'sensoren'];
}
if (!isset($config['sensor_order'])) {
    $config['sensor_order'] = [];
}

// Helper function für Category Emojis
function getCategoryEmoji($category) {
    $emojis = [
        'energie' => '⚡', 'wetter' => '️', 'fahrzeug' => '', 'temperatur' => '️',
        'sicherheit' => '', 'beleuchtung' => '', 'heizung' => '', 'wasser' => '',
        'netzwerk' => '', 'multimedia' => '', 'sensoren' => ''
    ];
    return $emojis[strtolower($category)] ?? '';
}

/*
 * ========================================
 * POST REQUEST HANDLERS
 * ========================================
 */

// SENSOR HINZUFÜGEN
if(isset($_POST['add_sensor'])) {
    $entity_id = trim($_POST['entity_id']);
    $description = trim($_POST['description']);
    $category = $_POST['new_category_input'] ? trim($_POST['new_category_input']) : trim($_POST['category']);
    $unit = trim($_POST['unit']);
    
    if($entity_id && $description && $category) {
        if(isset($config['sensors'][$entity_id])) {
            $error = "❌ Entity ID '$entity_id' existiert bereits!";
        } else {
            $config['sensors'][$entity_id] = [
                'description' => $description,
                'category' => strtolower($category),
                'unit' => $unit
            ];
            
            if($_POST['new_category_input'] && !in_array(strtolower($category), $config['category_order'])) {
                $config['category_order'][] = strtolower($category);
            }
            
            file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
            $success = "✅ Sensor '$description' erfolgreich hinzugefügt!";
        }
    } else {
        $error = "❌ Bitte füllen Sie alle Pflichtfelder aus!";
    }
}

// SENSOR BEARBEITEN
if(isset($_POST['edit_sensor'])) {
    $entity_id = $_POST['entity_id'];
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $unit = trim($_POST['unit']);
    
    if(isset($config['sensors'][$entity_id])) {
        $old_category = $config['sensors'][$entity_id]['category'];
        
        $config['sensors'][$entity_id]['description'] = $description;
        $config['sensors'][$entity_id]['category'] = $category;
        $config['sensors'][$entity_id]['unit'] = $unit;
        
        // Wenn Kategorie geändert wurde, aus alter Sortierung entfernen
        if($old_category !== $category) {
            if(isset($config['sensor_order'][$old_category])) {
                $config['sensor_order'][$old_category] = array_filter(
                    $config['sensor_order'][$old_category], 
                    function($id) use ($entity_id) { return $id !== $entity_id; }
                );
            }
        }
        
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        
        // AJAX Response für Auto-Save
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'success']);
            exit;
        }
        
        $success = "✅ Sensor '$entity_id' erfolgreich aktualisiert!";
    }
}

// SENSOR LÖSCHEN
if(isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if(isset($config['sensors'][$delete_id])) {
        $category = $config['sensors'][$delete_id]['category'];
        unset($config['sensors'][$delete_id]);
        
        // Auch aus Sortierung entfernen
        if(isset($config['sensor_order'][$category])) {
            $config['sensor_order'][$category] = array_filter(
                $config['sensor_order'][$category], 
                function($id) use ($delete_id) { return $id !== $delete_id; }
            );
        }
        
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $success = "✅ Sensor erfolgreich gelöscht!";
    }
}

// SENSOR-REIHENFOLGE SPEICHERN
if(isset($_POST['update_sensor_order'])) {
    $category = $_POST['category'];
    $sensor_order = $_POST['sensor_order'] ?? [];
    
    $valid_sensors = [];
    foreach($sensor_order as $entity_id) {
        if(isset($config['sensors'][$entity_id]) && $config['sensors'][$entity_id]['category'] === $category) {
            $valid_sensors[] = $entity_id;
        }
    }
    
    $config['sensor_order'][$category] = $valid_sensors;
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    
    echo json_encode(['status' => 'success', 'message' => "Reihenfolge für '$category' gespeichert"]);
    exit;
}

// KATEGORIE-REIHENFOLGE SPEICHERN
if(isset($_POST['update_category_order'])) {
    $new_order = $_POST['category_order'] ?? [];
    $config['category_order'] = $new_order;
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    $success = "✅ Kategorie-Reihenfolge erfolgreich gespeichert!";
}

// Sensoren nach Kategorien gruppieren
$sensors_by_category = [];
if(isset($config['sensors'])) {
    foreach($config['sensors'] as $entity_id => $sensor) {
        $category = $sensor['category'] ?? 'sensoren';
        $sensors_by_category[$category][] = array_merge($sensor, ['entity_id' => $entity_id]);
    }
    
    // Sortierung anwenden falls vorhanden
    foreach($sensors_by_category as $category => &$sensors) {
        if(isset($config['sensor_order'][$category])) {
            $ordered = [];
            $order = $config['sensor_order'][$category];
            
            // Erst sortierte hinzufügen
            foreach($order as $entity_id) {
                foreach($sensors as $key => $sensor) {
                    if($sensor['entity_id'] === $entity_id) {
                        $ordered[] = $sensor;
                        unset($sensors[$key]);
                        break;
                    }
                }
            }
            
            // Dann unsortierte anhängen
            $sensors = array_merge($ordered, array_values($sensors));
        }
    }
}

// Login-Formular anzeigen falls nicht eingeloggt
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Admin Login - Smart Home Dashboard</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .login-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
            .login-header { margin-bottom: 30px; }
            .login-header h1 { color: #333; font-size: 28px; margin-bottom: 10px; }
            .login-header p { color: #666; font-size: 14px; }
            .form-group { margin-bottom: 20px; text-align: left; }
            .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
            .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e1e1e1; border-radius: 8px; font-size: 14px; transition: border-color 0.3s ease; }
            .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
            .login-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; transition: transform 0.2s ease; }
            .login-btn:hover { transform: translateY(-2px); }
            .error { background: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fcc; }
            .dashboard-link { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
            .dashboard-link a { color: #667eea; text-decoration: none; font-size: 14px; }
            .dashboard-link a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1> Admin Login</h1>
                <p>Melden Sie sich an um das Dashboard zu verwalten</p>
            </div>
            
            <?php if(isset($login_error)): ?>
                <div class="error"><?= $login_error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label> Passwort</label>
                    <input type="password" name="password" placeholder="Admin-Passwort eingeben..." required autofocus>
                </div>
                
                <button type="submit" name="login" class="login-btn">
                     Anmelden
                </button>
            </form>
            
            <div class="dashboard-link">
                <a href="index.php">← Zurück zum Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ Admin Panel - Smart Home Dashboard</title>
    
    <!-- jQuery & jQuery UI für Drag & Drop -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <style>
        /* ========================================
         * GRUNDLEGENDE STYLES
         * ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #4a90e2;
            --primary-hover: #357abd;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --text-color: #333;
            --text-muted: #6c757d;
            --input-border: #ced4da;
            --input-bg: white;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* ========================================
         * HEADER & NAVIGATION
         * ======================================== */
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
        }

        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .admin-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a, .nav-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .nav-links a:hover, .nav-actions a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* ========================================
         * ALERT MESSAGES
         * ======================================== */
        .success, .error {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: slideIn 0.5s ease;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ========================================
         * FORM SECTIONS
         * ======================================== */
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color);
        }

        .form-section h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 22px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 8px;
            display: inline-block;
        }

        .form-section p {
            color: var(--text-muted);
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* ========================================
         * FORM ELEMENTS
         * ======================================== */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--input-border);
            border-radius: 6px;
            font-size: 14px;
            background: var(--input-bg);
            transition: all 0.3s ease;
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-group small {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 5px;
        }

        /* ========================================
         * BUTTONS
         * ======================================== */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* ========================================
         * SENSOR SORTIERUNG & DRAG & DROP
         * ======================================== */
        .sensor-sort-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid var(--primary-color);
        }

        .sensor-sort-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .category-sort-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .category-sort-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .category-sort-header h3 {
            color: var(--primary-color);
            font-size: 16px;
        }

        .category-sort-header span {
            color: var(--text-muted);
            font-size: 12px;
            background: var(--light-bg);
            padding: 4px 8px;
            border-radius: 10px;
        }

        /* ========================================
         * SORTABLE LISTS
         * ======================================== */
        .sensor-sortable {
            list-style: none;
            min-height: 50px;
            background: var(--light-bg);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .sensor-sortable-item {
            background: white;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            cursor: move;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
        }

        .sensor-sortable-item:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .sensor-sortable-item.ui-sortable-helper {
            transform: rotate(5deg);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .sensor-sortable-item.ui-sortable-placeholder {
            background: #e3f2fd;
            border: 2px dashed var(--primary-color);
            height: 60px;
            margin-bottom: 8px;
        }

        .drag-handle {
            color: var(--text-muted);
            font-size: 14px;
            cursor: grab;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .drag-handle:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* ========================================
         * INLINE EDITING
         * ======================================== */
        .sensor-edit-form {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 5px;
        }

        .inline-edit-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            width: 100%;
        }

        .edit-field {
            display: flex;
            align-items: center;
            gap: 5px;
            min-width: 120px;
            flex: 1;
        }

        .edit-field label {
            font-size: 14px;
            min-width: 20px;
            margin-bottom: 0;
        }

        .inline-input, .inline-select {
            padding: 6px 8px;
            border: 1px solid var(--input-border);
            border-radius: 4px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 13px;
            flex: 1;
            min-width: 80px;
        }

        .description-input {
            font-weight: 600;
            min-width: 150px;
        }

        .unit-input {
            max-width: 60px;
            text-align: center;
        }

        .entity-field {
            flex: 2;
        }

        .entity-display {
            background: rgba(0,0,0,0.05);
            padding: 6px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .entity-display:hover {
            background: rgba(0,123,255,0.1);
            transform: scale(1.02);
        }

        .copy-icon {
            opacity: 0.6;
            font-size: 10px;
        }

        .edit-actions {
            display: flex;
            gap: 5px;
            margin-left: auto;
        }

        .save-btn, .delete-btn {
            padding: 6px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            min-width: 30px;
        }

        .save-btn {
            background: #28a745;
            color: white;
        }

        .save-btn:hover {
            background: #218838;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .save-sort-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .save-sort-btn:hover {
            background: var(--primary-hover);
        }

        /* Auto-save Feedback */
        .saving {
            background: #fff3cd !important;
            border-color: #ffc107 !important;
        }

        .saved {
            background: #d4edda !important;
            border-color: #28a745 !important;
        }

        /* ========================================
         * CATEGORY SORTABLE
         * ======================================== */
        .category-sortable {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            list-style: none;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .category-sortable li {
            background: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: move;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-sortable li:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
        }

        /* ========================================
         * SENSOR TABLE
         * ======================================== */
        .sensor-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .sensor-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .sensor-table th,
        .sensor-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .sensor-table th {
            background: var(--light-bg);
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }

        .sensor-table tbody tr:hover {
            background: rgba(74, 144, 226, 0.05);
        }

        .sensor-table .entity-id {
            font-family: monospace;
            font-size: 12px;
            background: rgba(0,0,0,0.05);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .sensor-table .description {
            font-weight: 500;
        }

        .sensor-table .category {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--light-bg);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .sensor-table .unit {
            font-weight: 500;
            color: var(--primary-color);
        }

        .sensor-table .actions {
            display: flex;
            gap: 5px;
        }

        .sensor-table .actions .btn {
            padding: 6px 10px;
            font-size: 12px;
        }

        /* ========================================
         * RESPONSIVE DESIGN
         * ======================================== */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .admin-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-links, .nav-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .form-row {
                grid-template
        /* ========================================
         * RESPONSIVE DESIGN (Fortsetzung)
         * ======================================== */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .admin-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-links, .nav-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .sensor-sort-grid {
                grid-template-columns: 1fr;
            }

            .inline-edit-form {
                flex-direction: column;
                align-items: stretch;
            }

            .edit-field {
                min-width: auto;
            }

            .sensor-table {
                font-size: 12px;
            }

            .sensor-table th,
            .sensor-table td {
                padding: 8px 10px;
            }
        }

        /* ========================================
         * ANIMATIONS
         * ======================================== */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <h1>⚙️ Smart Home Dashboard - Admin Panel</h1>
            <p>Verwalten Sie alle Sensoren, Kategorien und Dashboard-Einstellungen</p>
            <div class="admin-nav">
                <div class="nav-links">
                    <a href="#add-sensor">➕ Sensor hinzufügen</a>
                    <a href="#sort-sensors"> Sensoren sortieren</a>
                    <a href="#manage-categories"> Kategorien verwalten</a>
                    <a href="#sensor-overview"> Sensor-Übersicht</a>
                </div>
                <div class="nav-actions">
                    <a href="index.php" target="_blank">️ Dashboard ansehen</a>
                    <a href="?logout=1"> Abmelden</a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if(isset($success)): ?>
            <div class="success fade-in"><?= $success ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="error fade-in"><?= $error ?></div>
        <?php endif; ?>

        <!-- NEUEN SENSOR HINZUFÜGEN -->
        <div class="form-section" id="add-sensor">
            <h2>➕ Neuen Sensor hinzufügen</h2>
            <p>Fügen Sie neue Home Assistant Sensoren zum Dashboard hinzu.</p>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label> Entity ID *</label>
                        <input type="text" name="entity_id" placeholder="z.B. sensor.temperature_living" required>
                        <small>Home Assistant Entity ID (eindeutig!)</small>
                    </div>
                    
                    <div class="form-group">
                        <label> Beschreibung *</label>
                        <input type="text" name="description" placeholder="z.B. Wohnzimmer Temperatur" required>
                        <small>Name der im Dashboard angezeigt wird</small>
                    </div>
                    
                    <div class="form-group">
                        <label> Kategorie *</label>
                        <select name="category" required>
                            <option value="">-- Kategorie wählen --</option>
                            <?php foreach($config['category_order'] as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                                    <?= getCategoryEmoji($cat) ?> <?= ucfirst(htmlspecialchars($cat)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Gruppierung für Dashboard</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label> Oder neue Kategorie</label>
                        <input type="text" name="new_category_input" placeholder="z.B. smarthome">
                        <small>Falls Kategorie nicht existiert</small>
                    </div>
                    
                    <div class="form-group">
                        <label> Einheit</label>
                        <input type="text" name="unit" placeholder="z.B. °C, %, W, kWh">
                        <small>Maßeinheit für den Sensor-Wert</small>
                    </div>
                </div>
                
                <button type="submit" name="add_sensor" class="btn btn-primary">
                    ✅ Sensor hinzufügen
                </button>
            </form>
        </div>

        <!-- SENSOR-REIHENFOLGE & BEARBEITUNG -->
        <div class="form-section sensor-sort-section" id="sort-sensors">
            <h2> Sensor-Reihenfolge & Bearbeitung</h2>
            <p><strong>Ziehen, bearbeiten und sortieren</strong> Sie alle Sensoren direkt hier. Änderungen werden automatisch gespeichert.</p>
            
            <div class="sensor-sort-grid">
                <?php foreach($config['category_order'] as $category): ?>
                    <?php if(isset($sensors_by_category[$category]) && !empty($sensors_by_category[$category])): ?>
                        <div class="category-sort-card">
                            <div class="category-sort-header">
                                <h3><?= getCategoryEmoji($category) ?> <?= ucfirst($category) ?></h3>
                                <span>(<?= count($sensors_by_category[$category]) ?> Sensoren)</span>
                            </div>
                            
                            <ul class="sensor-sortable" data-category="<?= htmlspecialchars($category) ?>">
                                <?php foreach($sensors_by_category[$category] as $sensor): ?>
                                    <li class="sensor-sortable-item" data-entity="<?= htmlspecialchars($sensor['entity_id']) ?>">
                                        
                                        <span class="drag-handle">⋮⋮</span>
                                        
                                        <div class="sensor-edit-form">
                                            <form class="inline-edit-form">
                                                <input type="hidden" name="entity_id" value="<?= htmlspecialchars($sensor['entity_id']) ?>">
                                                
                                                <div class="edit-field entity-field">
                                                    <label></label>
                                                    <div class="entity-display" onclick="copyToClipboard('<?= htmlspecialchars($sensor['entity_id']) ?>')" title="Klicken zum Kopieren">
                                                        <?= htmlspecialchars($sensor['entity_id']) ?>
                                                        <span class="copy-icon"></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="edit-field">
                                                    <label></label>
                                                    <input type="text" name="description" 
                                                           value="<?= htmlspecialchars($sensor['description']) ?>" 
                                                           class="inline-input description-input auto-save"
                                                           placeholder="Beschreibung">
                                                </div>
                                                
                                                <div class="edit-field">
                                                    <label></label>
                                                    <select name="category" class="inline-select auto-save">
                                                        <?php foreach($config['category_order'] as $cat): ?>
                                                            <option value="<?= htmlspecialchars($cat) ?>" 
                                                                    <?= ($cat === $sensor['category']) ? 'selected' : '' ?>>
                                                                <?= ucfirst($cat) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="edit-field">
                                                    <label></label>
                                                    <input type="text" name="unit" 
                                                           value="<?= htmlspecialchars($sensor['unit'] ?? '') ?>" 
                                                           class="inline-input unit-input auto-save"
                                                           placeholder="Einheit">
                                                </div>
                                                
                                                <div class="edit-actions">
                                                    <button type="button" class="delete-btn" onclick="deleteSensor('<?= htmlspecialchars($sensor['entity_id']) ?>')">
                                                        ️
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <button type="button" class="save-sort-btn" onclick="saveSensorOrder('<?= htmlspecialchars($category) ?>')">
                                 Reihenfolge speichern
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if(empty($sensors_by_category)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <h3> Noch keine Sensoren vorhanden</h3>
                    <p>Fügen Sie oben Ihren ersten Sensor hinzu!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- KATEGORIE-VERWALTUNG -->
        <div class="form-section" id="manage-categories">
            <h2> Kategorie-Reihenfolge verwalten</h2>
            <p>Ziehen Sie die Kategorien in die gewünschte Reihenfolge für das Dashboard.</p>
            
            <form method="POST">
                <ul class="category-sortable" id="category-sortable">
                    <?php foreach($config['category_order'] as $category): ?>
                        <li data-category="<?= htmlspecialchars($category) ?>">
                            <input type="hidden" name="category_order[]" value="<?= htmlspecialchars($category) ?>">
                            <?= getCategoryEmoji($category) ?> <?= ucfirst(htmlspecialchars($category)) ?>
                            <span>(<?= isset($sensors_by_category[$category]) ? count($sensors_by_category[$category]) : 0 ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <button type="submit" name="update_category_order" class="btn btn-primary">
                     Kategorie-Reihenfolge speichern
                </button>
            </form>
        </div>

        <!-- SENSOR-ÜBERSICHT -->
        <div class="form-section" id="sensor-overview">
            <h2> Sensor-Übersicht</h2>
            <p>Alle registrierten Sensoren auf einen Blick.</p>
            
            <?php if(!empty($config['sensors'])): ?>
                <div class="sensor-table-container">
                    <table class="sensor-table">
                        <thead>
                            <tr>
                                <th>Entity ID</th>
                                <th>Beschreibung</th>
                                <th>Kategorie</th>
                                <th>Einheit</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($config['sensors'] as $entity_id => $sensor): ?>
                                <tr>
                                    <td>
                                        <span class="entity-id"><?= htmlspecialchars($entity_id) ?></span>
                                    </td>
                                    <td>
                                        <span class="description"><?= htmlspecialchars($sensor['description']) ?></span>
                                    </td>
                                    <td>
                                        <span class="category">
                                            <?= getCategoryEmoji($sensor['category'] ?? 'sensoren') ?>
                                            <?= ucfirst(htmlspecialchars($sensor['category'] ?? 'sensoren')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="unit"><?= htmlspecialchars($sensor['unit'] ?? '-') ?></span>
                                    </td>
                                    <td class="actions">
                                        <a href="?delete=<?= urlencode($entity_id) ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Sensor \'<?= htmlspecialchars($entity_id) ?>\ wirklich löschen?')">
                                            ️ Löschen
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <h3> Keine Sensoren vorhanden</h3>
                    <p>Fügen Sie oben Ihren ersten Sensor hinzu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        $(document).ready(function() {
            // ========================================
            // DRAG & DROP FÜR SENSOREN
            // ========================================
            $('.sensor-sortable').sortable({
                handle: '.drag-handle',
                placeholder: 'ui-sortable-placeholder',
                helper: 'clone',
                opacity: 0.8,
                tolerance: 'pointer',
                start: function(event, ui) {
                    $(ui.item).addClass('ui-sortable-helper');
                },
                stop: function(event, ui) {
                    $(ui.item).removeClass('ui-sortable-helper');
                }
            }).disableSelection();

            // ========================================
            // DRAG & DROP FÜR KATEGORIEN
            // ========================================
            $('#category-sortable').sortable({
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                update: function() {
                    // Hidden inputs aktualisieren
                    $(this).find('li').each(function(index) {
                        $(this).find('input[name="category_order[]"]').val($(this).data('category'));
                    });
                }
            });

            // ========================================
            // AUTO-SAVE FÜR SENSOR-BEARBEITUNG
            // ========================================
            $('.auto-save').on('input change', function() {
                const input = $(this);
                const form = input.closest('.inline-edit-form');
                
                // Visual feedback
                input.addClass('saving');
                
                // Debounce
                clearTimeout(input.data('timeout'));
                input.data('timeout', setTimeout(function() {
                    autoSaveSensor(form, input);
                }, 500));
            });
        });

        // ========================================
        // FUNKTIONEN
        // ========================================
        
        /**
         * Sensor-Reihenfolge speichern
         */
        function saveSensorOrder(category) {
            const sortable = $(`.sensor-sortable[data-category="${category}"]`);
            const sensorOrder = [];
            
            sortable.find('.sensor-sortable-item').each(function() {
                sensorOrder.push($(this).data('entity'));
            });
            
            // AJAX Request
            $.post('admin.php', {
                update_sensor_order: true,
                category: category,
                sensor_order: sensorOrder
            }, function(response) {
                const data = JSON.parse(response);
                if(data.status === 'success') {
                    // Success feedback
                    const btn = $(`.save-sort-btn[onclick*="${category}"]`);
                    const originalText = btn.text();
                    btn.text('✅ Gespeichert!').css('background', '#28a745');
                    
                    setTimeout(() => {
                        btn.text(originalText).css('background', '');
                    }, 1500);
                    
                    console.log('✅', data.message);
                }
            }).fail(function() {
                alert('❌ Fehler beim Speichern der Reihenfolge');
            });
        }

        /**
         * Auto-Save für Sensor-Bearbeitung
         */
        function autoSaveSensor(form, changedInput) {
            const formData = new FormData();
            formData.append('edit_sensor', '1');
            formData.append('entity_id', form.find('input[name="entity_id"]').val());
            formData.append('description', form.find('input[name="description"]').val());
            formData.append('category', form.find('select[name="category"]').val());
            formData.append('unit', form.find('input[name="unit"]').val());
            
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                changedInput.removeClass('saving').addClass('saved');
                setTimeout(() => changedInput.removeClass('saved'), 1000);
                console.log('✅ Auto-save erfolgreich');
            })
            .catch(error => {
                console.error('❌ Auto-save Fehler:', error);
                changedInput.removeClass('saving');
                alert('❌ Fehler beim Speichern');
            });
        }

        /**
         * Sensor löschen
         */
        function deleteSensor(entityId) {
            if(confirm(`Sensor "${entityId}" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.`)) {
                window.location.href = `admin.php?delete=${encodeURIComponent(entityId)}`;
            }
        }

        /**
         * Copy to Clipboard
         */
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                console.log(' Kopiert:', text);
                
                // Visual feedback
                event.target.style.background = '#28a745';
                event.target.style.color = 'white';
                setTimeout(() => {
                    event.target.style.background = '';
                    event.target.style.color = '';
                }, 300);
            }).catch(function() {
                // Fallback für ältere Browser
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                console.log(' Kopiert (fallback):', text);
            });
        }
    </script>
</body>
</html>
