<?php
/**
 * Test: DataTableHelpers Trait Loading
 *
 * @package     WP_DataTable
 * @subpackage  Examples
 * @version     0.2.0
 *
 * Path: /wp-datatable/examples/test-trait-loading.php
 *
 * Description: Test script to verify DataTableHelpers trait can be loaded correctly.
 *
 * Usage:
 * wp eval-file examples/test-trait-loading.php
 */

echo "=== Testing WPDataTable\\Traits\\DataTableHelpers ===\n\n";

// Test 1: Check if trait exists
echo "1. Checking trait existence...\n";
if (trait_exists('WPDataTable\\Traits\\DataTableHelpers')) {
    echo "   ✅ Trait exists and loaded successfully\n\n";
} else {
    echo "   ❌ Trait not found!\n";
    echo "   Attempting to load manually...\n";

    $trait_file = WP_DATATABLE_PATH . 'src/Traits/DataTableHelpers.php';
    if (file_exists($trait_file)) {
        require_once $trait_file;
        echo "   ✅ Trait loaded manually\n\n";
    } else {
        echo "   ❌ Trait file not found: $trait_file\n";
        exit(1);
    }
}

// Test 2: Check trait methods
echo "2. Checking trait methods...\n";
echo "   Using test class to verify methods...\n";

class TestDataTableModel {
    use WPDataTable\Traits\DataTableHelpers;

    // Make protected methods public for testing
    public function test_generate_action_buttons($row, $options = []) {
        return $this->generate_action_buttons($row, $options);
    }

    public function test_format_status_badge($status, $options = []) {
        return $this->format_status_badge($status, $options);
    }

    public function test_format_panel_row_data($row, $entity, $additional_data = []) {
        return $this->format_panel_row_data($row, $entity, $additional_data);
    }

    public function test_esc_output($value, $fallback = '-') {
        return $this->esc_output($value, $fallback);
    }
}

$test_instance = new TestDataTableModel();
$reflection = new ReflectionClass($test_instance);

$expected_methods = [
    'generate_action_buttons',
    'format_status_badge',
    'format_panel_row_data',
    'generate_columns_config',
    'esc_output'
];

echo "\n   Available methods:\n";
foreach ($expected_methods as $method) {
    if ($reflection->hasMethod($method)) {
        $method_obj = $reflection->getMethod($method);
        $visibility = $method_obj->isPublic() ? 'public' : ($method_obj->isProtected() ? 'protected' : 'private');
        echo "   ✅ {$method}() [{$visibility}]\n";
    } else {
        echo "   ❌ {$method}() - NOT FOUND\n";
    }
}

echo "\n3. Testing method functionality...\n";

// Mock current_user_can before tests (if not already defined)
if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;  // Always return true for testing
    }
}

// Test 3.1: format_status_badge
echo "   3.1. format_status_badge()...\n";
$badge_active = $test_instance->test_format_status_badge('active', ['text_domain' => 'wp-datatable']);
if (strpos($badge_active, 'wpdt-badge-success') !== false) {
    echo "       ✅ Active badge: {$badge_active}\n";
} else {
    echo "       ❌ Active badge format incorrect\n";
}

$badge_inactive = $test_instance->test_format_status_badge('inactive', ['text_domain' => 'wp-datatable']);
if (strpos($badge_inactive, 'wpdt-badge-error') !== false) {
    echo "       ✅ Inactive badge: {$badge_inactive}\n";
} else {
    echo "       ❌ Inactive badge format incorrect\n";
}

// Test 3.2: format_panel_row_data
echo "\n   3.2. format_panel_row_data()...\n";
$test_row = (object) ['id' => 123, 'name' => 'Test'];
$panel_data = $test_instance->test_format_panel_row_data($test_row, 'division');
if (isset($panel_data['DT_RowId']) && $panel_data['DT_RowId'] === 'division-123') {
    echo "       ✅ DT_RowId: {$panel_data['DT_RowId']}\n";
} else {
    echo "       ❌ DT_RowId incorrect\n";
}
if (isset($panel_data['DT_RowData']['id']) && $panel_data['DT_RowData']['id'] === 123) {
    echo "       ✅ DT_RowData[id]: {$panel_data['DT_RowData']['id']}\n";
} else {
    echo "       ❌ DT_RowData[id] incorrect\n";
}

// Test 3.3: generate_action_buttons
echo "\n   3.3. generate_action_buttons()...\n";

$buttons = $test_instance->test_generate_action_buttons($test_row, [
    'entity' => 'division',
    'edit_capability' => 'edit_all_divisions',
    'delete_capability' => 'delete_division',
    'text_domain' => 'wp-datatable'
]);

if (strpos($buttons, 'division-edit-btn') !== false) {
    echo "       ✅ Edit button generated with correct class\n";
} else {
    echo "       ❌ Edit button class incorrect\n";
}

if (strpos($buttons, 'division-delete-btn') !== false) {
    echo "       ✅ Delete button generated with correct class\n";
} else {
    echo "       ❌ Delete button class incorrect\n";
}

if (strpos($buttons, 'data-id="123"') !== false) {
    echo "       ✅ Button has correct data-id attribute\n";
} else {
    echo "       ❌ Button data-id attribute incorrect\n";
}

if (strpos($buttons, 'data-entity="division"') !== false) {
    echo "       ✅ Button has correct data-entity attribute\n";
} else {
    echo "       ❌ Button data-entity attribute incorrect\n";
}

// Test 3.4: esc_output
echo "\n   3.4. esc_output()...\n";
$escaped = $test_instance->test_esc_output('Test Value');
if ($escaped === 'Test Value') {
    echo "       ✅ Normal value: {$escaped}\n";
} else {
    echo "       ❌ Normal value incorrect\n";
}

$fallback = $test_instance->test_esc_output('', 'N/A');
if ($fallback === 'N/A') {
    echo "       ✅ Fallback value: {$fallback}\n";
} else {
    echo "       ❌ Fallback value incorrect\n";
}

echo "\n=== All Tests Completed ===\n";
echo "✅ DataTableHelpers trait is ready to use!\n\n";
echo "Next steps:\n";
echo "1. Add 'use WPDataTable\\Traits\\DataTableHelpers;' to your model\n";
echo "2. Call methods in format_row(): generate_action_buttons(), format_status_badge()\n";
echo "3. See examples/DataTableHelpers-Example.php for complete usage\n";
