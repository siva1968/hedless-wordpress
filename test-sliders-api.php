<?php
/**
 * Test Sliders API Endpoints
 *
 * This script tests the newly implemented location-based sliders functionality.
 */

require_once __DIR__ . '/wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== LOCATION-BASED SLIDERS API TEST ===\n\n";

// Test 1: Get all sliders
echo "TEST 1: Get All Sliders\n";
echo "-----------------------------------\n";

$request = new WP_REST_Request('GET', '/lbp/v1/sliders');
$request->set_param('active_only', false);
$request->set_param('limit', 10);

$response = rest_do_request($request);
$data = $response->get_data();

echo "Total Sliders: " . ($data['total'] ?? 0) . "\n";
if (!empty($data['sliders'])) {
    foreach ($data['sliders'] as $slider) {
        echo "\nSlider: " . $slider['title'] . "\n";
        echo "  ID: " . $slider['id'] . "\n";
        echo "  Priority: " . $slider['priority'] . "\n";
        echo "  Active: " . ($slider['schedule']['is_active'] ? 'Yes' : 'No') . "\n";
        if (!empty($slider['button']['text'])) {
            echo "  Button: " . $slider['button']['text'] . " → " . $slider['button']['link'] . "\n";
        }
    }
} else {
    echo "ℹ️  No sliders found. Create some sliders in WordPress admin.\n";
}
echo "\n";

// Test 2: Get sliders for a specific location
echo "\nTEST 2: Get Sliders for Specific Location\n";
echo "-----------------------------------\n";

$locations = get_posts([
    'post_type' => 'lbp_location',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($locations)) {
    $location_id = $locations[0]->ID;
    echo "Testing with Location ID: $location_id\n";
    echo "Location Name: " . $locations[0]->post_title . "\n\n";

    $request = new WP_REST_Request('GET', '/lbp/v1/sliders');
    $request->set_param('location_id', $location_id);
    $request->set_param('active_only', true);

    $response = rest_do_request($request);
    $data = $response->get_data();

    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "❌ No locations found.\n\n";
}

// Test 3: Get active sliders only
echo "\nTEST 3: Get Active Sliders\n";
echo "-----------------------------------\n";

$request = new WP_REST_Request('GET', '/lbp/v1/sliders/active');
$response = rest_do_request($request);
$data = $response->get_data();

echo "Active Sliders Count: " . ($data['total'] ?? 0) . "\n";
if (!empty($data['sliders'])) {
    foreach ($data['sliders'] as $slider) {
        echo "\n✓ " . $slider['title'] . "\n";
        echo "  Style: Text " . $slider['style']['text_position'] . ", Color " . $slider['style']['text_color'] . "\n";
        echo "  Overlay: " . ($slider['style']['overlay_opacity'] * 100) . "%\n";
    }
}
echo "\n";

// Test 4: Get single slider
echo "\nTEST 4: Get Single Slider\n";
echo "-----------------------------------\n";

$sliders = get_posts([
    'post_type' => 'lbp_slider',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($sliders)) {
    $slider_id = $sliders[0]->ID;
    echo "Slider ID: $slider_id\n";
    echo "Slider Title: " . $sliders[0]->post_title . "\n\n";

    $request = new WP_REST_Request('GET', '/lbp/v1/sliders/' . $slider_id);
    $response = rest_do_request($request);
    $data = $response->get_data();

    if (isset($data['slider'])) {
        echo "Full Slider Data:\n";
        echo json_encode($data['slider'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "ℹ️  No sliders found.\n";
    echo "\nTo create a slider:\n";
    echo "1. Go to WordPress Admin → Products → Sliders\n";
    echo "2. Click 'Add New Slider'\n";
    echo "3. Add title, content, featured image\n";
    echo "4. Configure slider settings, location assignment, and schedule\n";
    echo "5. Publish the slider\n";
}
echo "\n";

// Test 5: Frontend Integration Example
echo "\nTEST 5: Frontend Integration Example\n";
echo "-----------------------------------\n";

echo "JavaScript/React Example:\n\n";
echo <<<'JAVASCRIPT'
// Fetch sliders for a location
async function fetchSliders(locationId) {
    const response = await fetch(
        `/wp-json/lbp/v1/sliders?location_id=${locationId}&active_only=true`
    );
    const data = await response.json();
    return data.sliders;
}

// React Component Example
function LocationSlider({ locationId }) {
    const [sliders, setSliders] = useState([]);

    useEffect(() => {
        fetchSliders(locationId).then(setSliders);
    }, [locationId]);

    return (
        <div className="slider-container">
            {sliders.map(slider => (
                <div key={slider.id} className="slider-slide"
                     style={{
                         color: slider.style.text_color,
                         textAlign: slider.style.text_position
                     }}>
                    {slider.image && (
                        <img src={slider.image.large} alt={slider.image.alt} />
                    )}
                    <div className="slider-content"
                         style={{
                             backgroundColor: `rgba(0,0,0,${slider.style.overlay_opacity})`
                         }}>
                        <h2>{slider.title}</h2>
                        {slider.subtitle && <p>{slider.subtitle}</p>}
                        {slider.button.text && (
                            <a href={slider.button.link}
                               target={slider.button.target}>
                                {slider.button.text}
                            </a>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
JAVASCRIPT;

echo "\n\n";

// Database information
echo "\nDATABASE INFORMATION\n";
echo "-----------------------------------\n";

$slider_count = wp_count_posts('lbp_slider');
echo "Total Sliders in Database:\n";
echo "  Published: " . ($slider_count->publish ?? 0) . "\n";
echo "  Draft: " . ($slider_count->draft ?? 0) . "\n";
echo "  Trash: " . ($slider_count->trash ?? 0) . "\n\n";

echo "=== SLIDERS API TESTS COMPLETE ===\n";
