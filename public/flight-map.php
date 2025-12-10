<?php
require_once '../includes/config.php';

$page_title = 'Карта рейсів - SkyBooking';

// Отримуємо всі активні рейси з координатами аеропортів
$flights = $pdo->query("
    SELECT 
        f.flight_id,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        al.name as airline_name,
        al.iata_code as airline_code,
        dep.city as dep_city,
        dep.iata_code as dep_code,
        dep.country as dep_country,
        dep.latitude as dep_lat,
        dep.longitude as dep_lon,
        arr.city as arr_city,
        arr.iata_code as arr_code,
        arr.country as arr_country,
        arr.latitude as arr_lat,
        arr.longitude as arr_lon,
        (SELECT COUNT(*) FROM tickets WHERE flight_id = f.flight_id) as booked_seats,
        f.available_seats
    FROM flights f
    JOIN airlines al ON f.airline_id = al.airline_id
    JOIN airports dep ON f.departure_airport_id = dep.airport_id
    JOIN airports arr ON f.arrival_airport_id = arr.airport_id
    WHERE DATE(f.departure_time) >= CURDATE()
    ORDER BY f.departure_time
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<style>
body {
    margin: 0;
    overflow: hidden;
    background: #0a0e27;
}

.map-container {
    width: 100vw;
    height: 100vh;
    position: relative;
    background: radial-gradient(ellipse at bottom, #1b2735 0%, #090a0f 100%);
    overflow: hidden;
}

#worldMap {
    width: 100%;
    height: 100%;
}

.controls {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    max-width: 300px;
}

.controls h2 {
    margin: 0 0 1rem 0;
    font-size: 1.3rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.stat-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem;
    border-radius: 8px;
    text-align: center;
}

.stat-item .label {
    font-size: 0.75rem;
    opacity: 0.9;
}

.stat-item .value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-top: 0.25rem;
}

.filter-group {
    margin-bottom: 1rem;
}

.filter-group label {
    display: block;
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.filter-group select {
    width: 100%;
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
}

.toggle-btn {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    transition: transform 0.2s;
}

.toggle-btn:hover {
    transform: translateY(-2px);
}

.toggle-btn:active {
    transform: translateY(0);
}

.flight-info {
    position: absolute;
    background: rgba(255, 255, 255, 0.98);
    padding: 1rem;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 999;
    max-width: 280px;
    border-left: 4px solid #667eea;
}

.flight-info.show {
    opacity: 1;
}

.flight-info .flight-number {
    font-size: 1.2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.flight-info .route {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.flight-info .detail {
    font-size: 0.85rem;
    color: #1e293b;
    margin: 0.25rem 0;
}

.legend {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.95);
    padding: 1rem;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
    font-size: 0.85rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

/* Анімація пульсації для аеропортів */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.3);
        opacity: 0.6;
    }
}

.loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 1.5rem;
    z-index: 1001;
}

.back-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.95);
    color: #1e293b;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    transition: transform 0.2s;
}

.back-btn:hover {
    transform: translateY(-2px);
}
</style>

<div class="map-container">
    <div class="loading" id="loading">✈️ Завантаження карти...</div>
    
    <a href="<?php echo BASE_URL; ?>/index.php" class="back-btn">← Назад</a>
    
    <div class="controls">
        <h2>🌍 Карта рейсів</h2>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="label">Рейсів</div>
                <div class="value" id="totalFlights"><?php echo count($flights); ?></div>
            </div>
            <div class="stat-item">
                <div class="label">Літають</div>
                <div class="value" id="activeFlights">0</div>
            </div>
        </div>
        
        <div class="filter-group">
            <label>🏢 Аеропорт відправлення</label>
            <select id="filterDeparture">
                <option value="">Всі аеропорти</option>
                <?php
                $airports = [];
                foreach ($flights as $f) {
                    $key = $f['dep_code'];
                    if (!isset($airports[$key])) {
                        $airports[$key] = $f['dep_city'] . ' (' . $f['dep_code'] . ')';
                    }
                }
                asort($airports);
                foreach ($airports as $code => $name) {
                    echo "<option value='$code'>$name</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>🛬 Аеропорт прибуття</label>
            <select id="filterArrival">
                <option value="">Всі аеропорти</option>
                <?php
                $airports = [];
                foreach ($flights as $f) {
                    $key = $f['arr_code'];
                    if (!isset($airports[$key])) {
                        $airports[$key] = $f['arr_city'] . ' (' . $f['arr_code'] . ')';
                    }
                }
                asort($airports);
                foreach ($airports as $code => $name) {
                    echo "<option value='$code'>$name</option>";
                }
                ?>
            </select>
        </div>
        
        <button class="toggle-btn" onclick="toggleAnimation()">⏸️ Пауза</button>
        <button class="toggle-btn" onclick="resetView()">🔄 Скинути вид</button>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #10b981;"></div>
            <span>Є місця (>50%)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #f59e0b;"></div>
            <span>Мало місць (20-50%)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #ef4444;"></div>
            <span>Майже повний (<20%)</span>
        </div>
    </div>
    
    <div id="worldMap"></div>
    <div class="flight-info" id="flightInfo"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css" />

<script>
const flightsData = <?php echo json_encode($flights); ?>;
console.log('Flights loaded:', flightsData.length);
console.log('Sample flight:', flightsData[0]);

let map, animationRunning = true, planeMarkers = [], routeLines = [];

// Ініціалізація карти
function initMap() {
    map = L.map('worldMap', {
        center: [50, 30],
        zoom: 3,
        minZoom: 2,
        maxZoom: 8,
        worldCopyJump: true
    });
    
    // Темна тема карти
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);
    
    document.getElementById('loading').style.display = 'none';
    console.log('Map initialized');
    renderFlights();
}

// Іконки літаків
const planeIcons = {
    green: L.divIcon({
        html: `<svg width="32" height="32" viewBox="0 0 24 24" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.8));">
                <path fill="#10b981" d="M21,16v-2l-8-5V3.5C13,2.67,12.33,2,11.5,2S10,2.67,10,3.5V9l-8,5v2l8-2.5V19l-2,1.5V22l3.5-1l3.5,1v-1.5L13,19v-5.5L21,16z"/>
               </svg>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    }),
    orange: L.divIcon({
        html: `<svg width="32" height="32" viewBox="0 0 24 24" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.8));">
                <path fill="#f59e0b" d="M21,16v-2l-8-5V3.5C13,2.67,12.33,2,11.5,2S10,2.67,10,3.5V9l-8,5v2l8-2.5V19l-2,1.5V22l3.5-1l3.5,1v-1.5L13,19v-5.5L21,16z"/>
               </svg>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    }),
    red: L.divIcon({
        html: `<svg width="32" height="32" viewBox="0 0 24 24" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.8));">
                <path fill="#ef4444" d="M21,16v-2l-8-5V3.5C13,2.67,12.33,2,11.5,2S10,2.67,10,3.5V9l-8,5v2l8-2.5V19l-2,1.5V22l3.5-1l3.5,1v-1.5L13,19v-5.5L21,16z"/>
               </svg>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    })
};

// Функція для створення точок дуги
function createArcPoints(lat1, lon1, lat2, lon2, numPoints) {
    const points = [];
    for (let i = 0; i <= numPoints; i++) {
        const t = i / numPoints;
        
        // Квадратична крива Безьє для красивої дуги
        const midLat = (lat1 + lat2) / 2;
        const midLon = (lon1 + lon2) / 2;
        
        // Підняття дуги (чим далі відстань, тим вища дуга)
        const distance = Math.sqrt(Math.pow(lat2 - lat1, 2) + Math.pow(lon2 - lon1, 2));
        const arcHeight = distance * 0.3; // 30% від відстані
        
        // Перпендикулярний вектор для підняття дуги
        const dx = lat2 - lat1;
        const dy = lon2 - lon1;
        const perpLat = -dy;
        const perpLon = dx;
        const perpLength = Math.sqrt(perpLat * perpLat + perpLon * perpLon);
        
        // Контрольна точка (середина + підняття)
        const controlLat = midLat + (perpLat / perpLength) * arcHeight;
        const controlLon = midLon + (perpLon / perpLength) * arcHeight;
        
        // Квадратична крива Безьє: B(t) = (1-t)²P0 + 2(1-t)tP1 + t²P2
        const lat = Math.pow(1 - t, 2) * lat1 + 2 * (1 - t) * t * controlLat + Math.pow(t, 2) * lat2;
        const lon = Math.pow(1 - t, 2) * lon1 + 2 * (1 - t) * t * controlLon + Math.pow(t, 2) * lon2;
        
        points.push([lat, lon]);
    }
    return points;
}

// Малювання рейсів
function renderFlights() {
    console.log('renderFlights called');
    
    // Очищення попередніх маркерів
    planeMarkers.forEach(m => map.removeLayer(m));
    routeLines.forEach(l => map.removeLayer(l));
    planeMarkers = [];
    routeLines = [];
    
    const depFilter = document.getElementById('filterDeparture').value;
    const arrFilter = document.getElementById('filterArrival').value;
    
    const filtered = flightsData.filter(f => {
        if (depFilter && f.dep_code !== depFilter) return false;
        if (arrFilter && f.arr_code !== arrFilter) return false;
        return true;
    });
    
    console.log('Filtered flights:', filtered.length);
    document.getElementById('totalFlights').textContent = filtered.length;
    
    filtered.forEach((flight, index) => {
        console.log('Processing flight:', flight.flight_number);
        const availability = (flight.available_seats - flight.booked_seats) / flight.available_seats;
        let color = '#ef4444'; // червоний
        let icon = planeIcons.red;
        
        if (availability > 0.5) {
            color = '#10b981'; // зелений
            icon = planeIcons.green;
        } else if (availability > 0.2) {
            color = '#f59e0b'; // помаранчевий
            icon = planeIcons.orange;
        }
        
        // Створення дуги маршруту
        const arcPoints = createArcPoints(
            parseFloat(flight.dep_lat), 
            parseFloat(flight.dep_lon),
            parseFloat(flight.arr_lat), 
            parseFloat(flight.arr_lon),
            30 // кількість точок
        );
        
        const line = L.polyline(arcPoints, {
            color: color,
            weight: 2,
            opacity: 0.6,
            smoothFactor: 1
        }).addTo(map);
        
        routeLines.push(line);
        
        // Аеропорти
        L.circleMarker([flight.dep_lat, flight.dep_lon], {
            radius: 6,
            fillColor: '#667eea',
            color: 'white',
            weight: 2,
            fillOpacity: 0.8
        }).addTo(map).bindPopup(`
            <strong>${flight.dep_city} (${flight.dep_code})</strong><br>
            ${flight.dep_country}
        `);
        
        L.circleMarker([flight.arr_lat, flight.arr_lon], {
            radius: 6,
            fillColor: '#764ba2',
            color: 'white',
            weight: 2,
            fillOpacity: 0.8
        }).addTo(map).bindPopup(`
            <strong>${flight.arr_city} (${flight.arr_code})</strong><br>
            ${flight.arr_country}
        `);
        
        // Літак (анімований)
        const planeMarker = L.marker([flight.dep_lat, flight.dep_lon], { icon: icon })
            .addTo(map);
        
        planeMarker.flightData = flight;
        planeMarker.arcPoints = arcPoints; // Зберігаємо точки дуги
        planeMarker.progress = Math.random(); // Випадкова початкова позиція
        
        planeMarker.on('mouseover', function(e) {
            showFlightInfo(e, flight);
        });
        
        planeMarker.on('mouseout', function() {
            document.getElementById('flightInfo').classList.remove('show');
        });
        
        planeMarkers.push(planeMarker);
    });
    
    console.log('Created', planeMarkers.length, 'plane markers');
    
    // Запуск анімації
    if (animationRunning) {
        animatePlanes();
    }
}

// Анімація літаків
function animatePlanes() {
    if (!animationRunning) return;
    
    let activeCount = 0;
    
    planeMarkers.forEach(marker => {
        if (!marker.arcPoints) return;
        
        marker.progress += 0.002; // Швидкість руху
        
        if (marker.progress >= 1) {
            marker.progress = 0; // Повторення маршруту
        }
        
        activeCount++;
        
        // Знаходимо позицію на дузі
        const arcIndex = Math.floor(marker.progress * (marker.arcPoints.length - 1));
        const nextIndex = Math.min(arcIndex + 1, marker.arcPoints.length - 1);
        
        const currentPoint = marker.arcPoints[arcIndex];
        const nextPoint = marker.arcPoints[nextIndex];
        
        // Встановлюємо позицію
        marker.setLatLng(currentPoint);
        
        // Обертання літака в напрямку руху
        const angle = Math.atan2(
            nextPoint[1] - currentPoint[1],
            nextPoint[0] - currentPoint[0]
        ) * 180 / Math.PI;
        
        const element = marker.getElement();
        if (element) {
            const svg = element.querySelector('svg');
            if (svg) {
                svg.style.transform = `rotate(${angle + 90}deg)`;
            }
        }
    });
    
    document.getElementById('activeFlights').textContent = activeCount;
    
    requestAnimationFrame(animatePlanes);
}

// Показати інфо про рейс
function showFlightInfo(e, flight) {
    const info = document.getElementById('flightInfo');
    const availability = flight.available_seats - flight.booked_seats;
    
    info.innerHTML = `
        <div class="flight-number">${flight.airline_name} ${flight.flight_number}</div>
        <div class="route">
            ${flight.dep_city} (${flight.dep_code}) → ${flight.arr_city} (${flight.arr_code})
        </div>
        <div class="detail">⏰ Відправлення: ${new Date(flight.departure_time).toLocaleString('uk-UA')}</div>
        <div class="detail">🪑 Вільних місць: ${availability} / ${flight.available_seats}</div>
        <div class="detail">📊 Зайнято: ${Math.round(flight.booked_seats / flight.available_seats * 100)}%</div>
    `;
    
    info.style.left = e.originalEvent.pageX + 20 + 'px';
    info.style.top = e.originalEvent.pageY + 20 + 'px';
    info.classList.add('show');
}

// Управління анімацією
function toggleAnimation() {
    animationRunning = !animationRunning;
    const btn = event.target;
    btn.textContent = animationRunning ? '⏸️ Пауза' : '▶️ Продовжити';
    if (animationRunning) animatePlanes();
}

function resetView() {
    map.setView([50, 30], 3);
}

// Фільтри
document.getElementById('filterDeparture').addEventListener('change', renderFlights);
document.getElementById('filterArrival').addEventListener('change', renderFlights);

// Запуск
initMap();
</script>

<?php require_once '../includes/footer.php'; ?>
