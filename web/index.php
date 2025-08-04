<?php
// UTF-8 Headers und Encoding setzen
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'utf-8');

$ha_url = "http://10.11.12.33:8123/api/states";
$ha_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhZjQ4MTBlNWYxZjY0NmM5OThlYTIxZjNiMjJhYTNhYyIsImlhdCI6MTc1NDI5NDg4MCwiZXhwIjoyMDY5NjU0ODgwfQ.k1I56BrKB5fnV-1lfIwgD3g7kXe4nrwNnp6vIFzmbSI";

// Check if this is an AJAX request for a single sensor
if (isset($_GET['ajax']) && isset($_GET['entity_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $entity_id = $_GET['entity_id'];
    $single_sensor_url = $ha_url . '/' . $entity_id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $single_sensor_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $ha_token,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    
    echo $response;
    exit;
}

// Fetch all sensors for initial load
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ha_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $ha_token,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!$data || isset($data['error'])) {
    die("Error fetching data from Home Assistant");
}

// Funktion um Icon basierend auf Kategorie zu bestimmen
function getIconForCategory($category) {
    $icons = [
        'energie' => 'fa-bolt',
        'wetter' => 'fa-cloud-sun',
        'fahrzeug' => 'fa-car',
        'temperatur' => 'fa-thermometer-half',
        'sicherheit' => 'fa-shield-alt',
        'beleuchtung' => 'fa-lightbulb',
        'heizung' => 'fa-fire',
        'wasser' => 'fa-tint',
        'netzwerk' => 'fa-wifi',
        'multimedia' => 'fa-tv',
        'sensoren' => 'fa-microchip'
    ];
    return $icons[strtolower($category)] ?? 'fa-microchip';
}

// Sensor configuration aus JSON-Datei laden
$config_file = 'sensor_config.json';
$sensor_configs = [];
$category_order = [];

if(file_exists($config_file)) {
    $config_data = json_decode(file_get_contents($config_file), true);
    if($config_data && isset($config_data['sensors'])) {
        $sensor_configs = $config_data['sensors'];
    }
    if(isset($config_data['category_order'])) {
        $category_order = $config_data['category_order'];
    }
}

// Sprachunterstützung
$language = isset($_GET['lang']) ? $_GET['lang'] : 'de';

$texts = [
    'de' => [
        'title' => 'Home Assistant Dashboard',
        'refresh_all' => 'Alle aktualisieren',
        'last_updated' => 'Zuletzt aktualisiert',
        'light_mode' => 'Heller Modus',
        'dark_mode' => 'Dunkler Modus',
        'no_data' => 'Keine Daten',
        'error' => 'Fehler',
        'loading' => 'Lädt...',
        'admin_panel' => 'Admin Panel'
    ],
    'en' => [
        'title' => 'Home Assistant Dashboard',
        'refresh_all' => 'Refresh All',
        'last_updated' => 'Last updated',
        'light_mode' => 'Light Mode',
        'dark_mode' => 'Dark Mode',
        'no_data' => 'No Data',
        'error' => 'Error',
        'loading' => 'Loading...',
        'admin_panel' => 'Admin Panel'
    ]
];

$current_texts = $texts[$language];

// Sensoren nach Kategorien gruppieren
$sensors_by_category = [];
foreach ($data as $entity) {
    $entity_id = $entity['entity_id'];
    
    if (isset($sensor_configs[$entity_id])) {
        $config = $sensor_configs[$entity_id];
        $category = ucfirst($config['category']);
        
        if (!isset($sensors_by_category[$category])) {
            $sensors_by_category[$category] = [];
        }
        
        $sensors_by_category[$category][] = [
            'entity' => $entity,
            'config' => $config
        ];
    }
}

// Kategorien nach definierter Reihenfolge sortieren
if (!empty($category_order)) {
    $ordered_categories = [];
    
    // Erst die Kategorien in der definierten Reihenfolge
    foreach ($category_order as $category) {
        $category_formatted = ucfirst($category);
        if (isset($sensors_by_category[$category_formatted])) {
            $ordered_categories[$category_formatted] = $sensors_by_category[$category_formatted];
        }
    }
    
    // Dann noch nicht definierte Kategorien anhängen
    foreach ($sensors_by_category as $category => $sensors) {
        if (!isset($ordered_categories[$category])) {
            $ordered_categories[$category] = $sensors;
        }
    }
    
    $sensors_by_category = $ordered_categories;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_texts['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #03DAC6;
            --background-color: #121212;
            --surface-color: #1E1E1E;
            --on-surface-color: #FFFFFF;
            --on-background-color: #FFFFFF;
            --error-color: #CF6679;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
        }

        [data-theme="light"] {
            --background-color: #FAFAFA;
            --surface-color: #FFFFFF;
            --on-surface-color: #000000;
            --on-background-color: #000000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--on-background-color);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .container {
            max-width: 1400px; /* Erweitert für 4 Kacheln */
            margin: 0 auto;
            padding: 0 1rem;
        }

        .category {
            margin-bottom: 3rem;
        }

        .category-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        /* ================================
         * 4 KACHELN PRO REIHE
         * ================================ */
        .sensors-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        /* Responsive Breakpoints für 4-Spalten-Layout */
        @media (max-width: 1400px) {
            .sensors-grid {
                grid-template-columns: repeat(3, 1fr); /* 3 Spalten bei mittleren Bildschirmen */
            }
        }

        @media (max-width: 1024px) {
            .sensors-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 Spalten bei Tablets */
            }
        }

        @media (max-width: 768px) {
            .sensors-grid {
                grid-template-columns: 1fr; /* 1 Spalte bei Handys */
                gap: 1rem;
            }
        }

        .sensor-card {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 1.8rem; /* Leicht reduziert für 4 Kacheln */
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            min-height: 280px; /* Einheitliche Höhe */
        }

        .sensor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 60px rgba(0,0,0,0.2);
        }

        .sensor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .sensor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.2rem;
        }

        .sensor-info h3 {
            font-size: 1.1rem; /* Leicht kleiner für 4 Kacheln */
            margin-bottom: 0.5rem;
            color: var(--on-surface-color);
            line-height: 1.3;
        }

        .sensor-icon {
            font-size: 2.5rem; /* Leicht reduziert */
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            flex-shrink: 0;
        }

        .sensor-value {
            text-align: center;
            margin: 1.5rem 0; /* Weniger Abstand */
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .sensor-value .value {
            font-size: 2.5rem; /* Etwas kleiner für 4 Kacheln */
            font-weight: 300;
            color: var(--primary-color);
            display: block;
            line-height: 1;
        }

        .sensor-value .unit {
            font-size: 1.1rem;
            color: #888;
            margin-top: 0.5rem;
        }

        .sensor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .last-updated {
            font-size: 0.85rem; /* Leicht kleiner */
            color: #888;
        }

        .refresh-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 36px; /* Etwas kleiner */
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .refresh-btn:hover {
            background: var(--secondary-color);
            transform: rotate(90deg);
        }

        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading .refresh-btn {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .sensor-card {
                padding: 1.5rem;
                min-height: 260px;
            }

            .sensor-value .value {
                font-size: 2.2rem;
            }

            .header-controls {
                flex-direction: column;
                align-items: center;
            }

            .container {
                max-width: none;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sensor-card.energie .sensor-icon {
            color: #FFC107;
        }

        .sensor-card.wetter .sensor-icon {
            color: #2196F3;
        }

        .sensor-card.fahrzeug .sensor-icon {
            color: #4CAF50;
        }
    </style>
</head>
<body data-theme="dark">
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> <?php echo $current_texts['title']; ?></h1>
            
            <div class="header-controls">
                <button class="btn" onclick="refreshAllSensors()">
                    <i class="fas fa-sync-alt"></i>
                    <?php echo $current_texts['refresh_all']; ?>
                </button>
                
                <button class="btn" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                    <span class="theme-text"><?php echo $current_texts['dark_mode']; ?></span>
                </button>
                
                <a href="admin.php" class="btn">
                    <i class="fas fa-cog"></i>
                    <?php echo $current_texts['admin_panel']; ?>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (empty($sensors_by_category)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h2>Keine konfigurierten Sensoren gefunden</h2>
                <p>Gehen Sie zum <a href="admin.php">Admin Panel</a> um Sensoren zu konfigurieren.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($sensors_by_category as $category => $sensors): ?>
        <div class="category fade-in">
            <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
            
            <div class="sensors-grid">
                <?php foreach ($sensors as $sensor_data): 
                    $entity = $sensor_data['entity'];
                    $config = $sensor_data['config'];
                    $entity_id = $entity['entity_id'];
                    
                    // Zeitstempel formatieren
                    $last_updated = '';
                    if (isset($entity['last_updated'])) {
                        $timestamp = strtotime($entity['last_updated']);
                        $last_updated = date('H:i:s', $timestamp);
                    }
                    
                    // Icon bestimmen
                    $icon = getIconForCategory($config['category']);
                ?>
                
                <div class="sensor-card <?php echo strtolower($config['category']); ?>" 
                     data-entity-id="<?php echo htmlspecialchars($entity_id); ?>">
                    
                    <div class="sensor-header">
                        <div class="sensor-info">
                            <h3><?php echo htmlspecialchars($config['description']); ?></h3>
                        </div>
                        <div class="sensor-icon">
                            <i class="fas <?php echo htmlspecialchars($icon); ?>"></i>
                        </div>
                    </div>
                    
                    <div class="sensor-value">
                        <span class="value"><?php echo htmlspecialchars($entity['state']); ?></span>
                        <?php if (!empty($config['unit'])): ?>
                            <div class="unit"><?php echo htmlspecialchars($config['unit']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sensor-footer">
                        <div class="last-updated" title="<?php echo isset($entity['last_updated']) ? date('d.m.Y H:i:s', strtotime($entity['last_updated'])) : 'Unbekannt'; ?>">
                            <i class="fas fa-clock"></i>
                            <?php echo $last_updated; ?>
                        </div>
                        <button class="refresh-btn" onclick="refreshSensor(this.closest('.sensor-card'))">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.querySelector('.header-controls .btn i');
            const themeText = document.querySelector('.theme-text');
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = '<?php echo $current_texts['light_mode']; ?>';
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = '<?php echo $current_texts['dark_mode']; ?>';
            }
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const themeIcon = document.querySelector('.header-controls .btn i');
            const themeText = document.querySelector('.theme-text');
            
            document.body.setAttribute('data-theme', savedTheme);
            
            if (savedTheme === 'dark') {
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = '<?php echo $current_texts['dark_mode']; ?>';
            } else {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = '<?php echo $current_texts['light_mode']; ?>';
            }
        }

        document.addEventListener('DOMContentLoaded', loadTheme);

        function refreshSensor(cardElement) {
            const entityId = cardElement.getAttribute('data-entity-id');
            
            cardElement.classList.add('loading');
            
            fetch(`?ajax=1&entity_id=${encodeURIComponent(entityId)}`)
                .then(response => response.json())
                .then(data => {
                    const valueElement = cardElement.querySelector('.sensor-value .value');
                    if (valueElement) {
                        valueElement.textContent = data.state;
                    }
                    
                    const timeElement = cardElement.querySelector('.last-updated');
                    if (timeElement && data.last_updated) {
                        const lastUpdated = new Date(data.last_updated);
                        const timeString = lastUpdated.toLocaleTimeString('de-DE');
                        timeElement.innerHTML = `<i class="fas fa-clock"></i> ${timeString}`;
                        timeElement.setAttribute('title', lastUpdated.toLocaleString('de-DE'));
                    }
                    
                    cardElement.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error refreshing sensor:', error);
                    cardElement.classList.remove('loading');
                    
                    cardElement.style.borderColor = 'var(--error-color)';
                    setTimeout(() => {
                        cardElement.style.borderColor = '';
                    }, 2000);
                });
        }

        function refreshAllSensors() {
            const sensorCards = document.querySelectorAll('.sensor-card');
            
            sensorCards.forEach((card, index) => {
                setTimeout(() => {
                    refreshSensor(card);
                }, index * 100);
            });
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshAllSensors, 30000);
    </script>
</body>
</html>
