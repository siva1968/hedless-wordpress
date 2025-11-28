# Product Categories and Filters Configuration

Based on AF Website changes.xlsx specifications.

---

## Home Screen Categories

**Display Format:** 2 columns, slideable carousel

| Order | Category Name          |
|-------|------------------------|
| 1     | AF Luxe               |
| 2     | Sofa sets             |
| 3     | Recliners             |
| 4     | Tables                |
| 5     | Beds                  |
| 6     | Mattress              |
| 7     | Dining Sets           |
| 8     | Storage Units         |
| 9     | Outdoor furniture     |
| 10    | Study & Office        |
| 11    | Bean Bags & Pouffes   |
| 12    | Décor                 |

---

## Product Filters by Category

### 1. SOFAS

#### Price Range
- Custom price range filter

#### Availability
- In stock
- Out of stock

#### Type
- Sofa sets
- Recliner Sets
- Loungers sofas
- Corner Sofas
- Sofa Cum Beds
- Diwans

#### Size
- 3+2+1
- 3+2
- 3+1+1
- 3+2+C
- 1 seater
- 2 seater
- 3 seater
- 4 seater

#### Material
- Fabric
- Body touch leather
- Full leather
- Wood
- Metal
- Micro Fabric
- Vegan Leather

#### Colour
- Beige
- Blue
- Brown
- Cream
- Black
- Green
- Grey
- Peach
- Pink
- Teal
- Yellow
- Red

#### Discount %
- 0-15%
- 16-35%
- Above 35%

---

### 2. RECLINERS

#### Price Range
- Custom price range filter

#### Availability
- In stock
- Out of stock

#### Type
- Motorised Recliner Sets
- Recliner Sets
- Loungers
- Corner

#### Size
- 3+2+1
- 3+2
- 3+1+1
- 3+2+C
- 1 seater
- 2 seater
- 3 seater
- 4 seater

#### Material
- Fabric
- Body touch leather
- Full leather
- Wood
- Metal
- Micro Fabric
- Vegan Leather

#### Colour
- Beige
- Blue
- Brown
- Cream
- Black
- Green
- Grey
- Peach
- Pink
- Teal
- Yellow
- Red

#### Mechanism
- Motorised
- Manual

#### Discount %
- 0-15%
- 16-35%
- Above 35%

---

### 3. TABLES

#### Price Range
- Custom price range filter

#### Availability
- Available (no specific stock status)

#### Type
- Centre Tables
- End Tables
- Console Tables
- Nesting Tables

#### Size
- 2ft
- 3ft
- 4ft
- 5ft

#### Material
- Marble
- Solid Wood
- Engineered Wood

#### Discount %
- Custom discount filter (no predefined ranges)

---

### 4. CHAIRS

#### Price Range
- Custom price range filter

#### Availability
- Available (no specific stock status)

#### Type
- Accent Chairs
- Folding Chairs
- Café Chairs
- Rocking Chairs
- Stools

#### Size
- 1 Seater

#### Material
- Fabric
- Body touch leather
- Full leather
- Wood
- Metal
- Micro Fabric
- Vegan Leather

#### Discount %
- Custom discount filter (no predefined ranges)

---

### 5. BEDS

#### Price Range
- Custom price range filter

#### Availability
- In stock
- Out of stock

#### Type
- Solid Wood Beds
- Upholstered Beds
- Engineered Wood Beds
- Hydraulic Storage Beds
- Poster Beds
- Metal Beds

#### Size
- King / 6FT Beds
- Queen / 5FT Beds
- Double / 4FT Beds
- Single / 3FT Beds
- Bunk Beds
- Kids Beds

#### Material
- Solid wood
- Engineered wood

#### Storage
- With Storage
- Without Storage
- Hydraulic Storage

#### Head board type
- Solid
- Upholstered

#### Upholstery
- Fabric
- Velvet
- Leather
- Vegan Leather

#### Discount %
- 0-15%
- 16-35%
- Above 35%

---

### 6. MATTRESS

#### Price Range
- Custom price range filter

#### Availability
- In stock
- Out of stock

#### Type
- Memory Foam Mattress
- Foam Mattress
- Latex Mattress
- Spring Mattress
- Coir Mattress
- Bonnel Spring Mattress
- Pocket Spring Mattress
- Coir and Foam Mattress

#### Size
- King / 6FT Beds
- Queen / 5FT Beds
- Double / 4FT Beds
- Single / 3FT Beds
- King XL / 7FT Beds

#### Brand
- Reclix
- Century
- Kurlon
- Sleepwell
- DuroFlex

#### Mattress Thickness
- 4 inch
- 5 inch
- 6 inch
- 7 inch
- 8 inch
- 10 inch
- 12 inch

#### Discount %
- Custom discount filter (no predefined ranges)

---

## Filter Implementation Guidelines

### WooCommerce Taxonomy Mapping

To implement these filters in WooCommerce, use the following taxonomies:

#### Product Attributes (for filtering)
```php
// Create custom product attributes
- pa_type           (Product Type)
- pa_size           (Product Size)
- pa_material       (Material)
- pa_colour         (Colour)
- pa_storage        (Storage Type)
- pa_headboard      (Headboard Type)
- pa_upholstery     (Upholstery Type)
- pa_brand          (Brand)
- pa_mechanism      (Recliner Mechanism)
- pa_thickness      (Mattress Thickness)
- pa_discount_range (Discount Range)
```

#### Stock Status
Use WooCommerce built-in stock management:
- `instock`
- `outofstock`

#### Price Range
Use WooCommerce price filtering with custom ranges

### REST API Filter Parameters

When implementing in the headless API, support these query parameters:

```
GET /wp-json/lbp/v1/products?category={category}&filters={json}

Example:
/wp-json/lbp/v1/products?category=sofas&filters={"type":"Corner Sofas","size":"3+2+1","material":"Fabric","colour":"Beige"}
```

### Filter Query Structure

```json
{
  "category": "sofas",
  "filters": {
    "availability": "instock",
    "type": ["Sofa sets", "Recliner Sets"],
    "size": ["3+2+1", "3+2"],
    "material": ["Fabric", "Vegan Leather"],
    "colour": ["Beige", "Grey"],
    "discount_range": "16-35%",
    "price_min": 10000,
    "price_max": 50000
  },
  "location_id": 123,
  "per_page": 20,
  "page": 1,
  "orderby": "date",
  "order": "desc"
}
```

### Frontend Filter UI Example

```javascript
// React/Next.js Filter Component Structure
const productFilters = {
  sofas: {
    availability: ['In stock', 'Out of stock'],
    type: ['Sofa sets', 'Recliner Sets', 'Loungers sofas', 'Corner Sofas', 'Sofa Cum Beds', 'Diwans'],
    size: ['3+2+1', '3+2', '3+1+1', '3+2+C', '1 seater', '2 seater', '3 seater', '4 seater'],
    material: ['Fabric', 'Body touch leather', 'Full leather', 'Wood', 'Metal', 'Micro Fabric', 'Vegan Leather'],
    colour: ['Beige', 'Blue', 'Brown', 'Cream', 'Black', 'Green', 'Grey', 'Peach', 'Pink', 'Teal', 'Yellow', 'Red'],
    discount: ['0-15%', '16-35%', 'Above 35%']
  },
  recliners: {
    availability: ['In stock', 'Out of stock'],
    type: ['Motorised Recliner Sets', 'Recliner Sets', 'Loungers', 'Corner'],
    size: ['3+2+1', '3+2', '3+1+1', '3+2+C', '1 seater', '2 seater', '3 seater', '4 seater'],
    material: ['Fabric', 'Body touch leather', 'Full leather', 'Wood', 'Metal', 'Micro Fabric', 'Vegan Leather'],
    colour: ['Beige', 'Blue', 'Brown', 'Cream', 'Black', 'Green', 'Grey', 'Peach', 'Pink', 'Teal', 'Yellow', 'Red'],
    mechanism: ['Motorised', 'Manual'],
    discount: ['0-15%', '16-35%', 'Above 35%']
  },
  tables: {
    type: ['Centre Tables', 'End Tables', 'Console Tables', 'Nesting Tables'],
    size: ['2ft', '3ft', '4ft', '5ft'],
    material: ['Marble', 'Solid Wood', 'Engineered Wood']
  },
  chairs: {
    type: ['Accent Chairs', 'Folding Chairs', 'Café Chairs', 'Rocking Chairs', 'Stools'],
    size: ['1 Seater'],
    material: ['Fabric', 'Body touch leather', 'Full leather', 'Wood', 'Metal', 'Micro Fabric', 'Vegan Leather']
  },
  beds: {
    availability: ['In stock', 'Out of stock'],
    type: ['Solid Wood Beds', 'Upholstered Beds', 'Engineered Wood Beds', 'Hydraulic Storage Beds', 'Poster Beds', 'Metal Beds'],
    size: ['King / 6FT Beds', 'Queen / 5FT Beds', 'Double / 4FT Beds', 'Single / 3FT Beds', 'Bunk Beds', 'Kids Beds'],
    material: ['Solid wood', 'Engineered wood'],
    storage: ['With Storage', 'Without Storage', 'Hydraulic Storage'],
    headboard_type: ['Solid', 'Upholstered'],
    upholstery: ['Fabric', 'Velvet', 'Leather', 'Vegan Leather'],
    discount: ['0-15%', '16-35%', 'Above 35%']
  },
  mattress: {
    availability: ['In stock', 'Out of stock'],
    type: ['Memory Foam Mattress', 'Foam Mattress', 'Latex Mattress', 'Spring Mattress', 'Coir Mattress', 'Bonnel Spring Mattress', 'Pocket Spring Mattress', 'Coir and Foam Mattress'],
    size: ['King / 6FT Beds', 'Queen / 5FT Beds', 'Double / 4FT Beds', 'Single / 3FT Beds', 'King XL / 7FT Beds'],
    brand: ['Reclix', 'Century', 'Kurlon', 'Sleepwell', 'DuroFlex'],
    thickness: ['4 inch', '5 inch', '6 inch', '7 inch', '8 inch', '10 inch', '12 inch']
  }
};
```

---

## Implementation Steps

### Step 1: Create WooCommerce Product Attributes

```php
// Add to functions.php or create a plugin
function af_create_product_attributes() {
    $attributes = [
        'pa_type' => 'Product Type',
        'pa_size' => 'Size',
        'pa_material' => 'Material',
        'pa_colour' => 'Colour',
        'pa_storage' => 'Storage',
        'pa_headboard' => 'Headboard Type',
        'pa_upholstery' => 'Upholstery',
        'pa_brand' => 'Brand',
        'pa_mechanism' => 'Mechanism',
        'pa_thickness' => 'Thickness',
        'pa_discount_range' => 'Discount Range'
    ];

    foreach ($attributes as $slug => $name) {
        if (!taxonomy_exists($slug)) {
            register_taxonomy($slug, 'product', [
                'label' => $name,
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
                'query_var' => true,
                'rewrite' => ['slug' => $slug],
            ]);
        }
    }
}
add_action('init', 'af_create_product_attributes');
```

### Step 2: Extend Location-Based Products API

Add filtering capabilities to the existing `/wp-json/lbp/v1/products` endpoint:

```php
// In wp-content/plugins/location-based-products/includes/rest-api.php

public function get_filtered_products($request) {
    $location_id = $request->get_param('location_id');
    $filters = $request->get_param('filters');

    // Build WP_Query args
    $args = [
        'post_type' => 'product',
        'posts_per_page' => $request->get_param('per_page') ?: 20,
        'paged' => $request->get_param('page') ?: 1,
        'tax_query' => [],
        'meta_query' => []
    ];

    // Add category filter
    if ($category = $request->get_param('category')) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $category
        ];
    }

    // Add attribute filters
    if (!empty($filters)) {
        foreach ($filters as $attribute => $values) {
            if (is_array($values)) {
                $args['tax_query'][] = [
                    'taxonomy' => 'pa_' . $attribute,
                    'field' => 'slug',
                    'terms' => array_map('sanitize_title', $values),
                    'operator' => 'IN'
                ];
            }
        }
    }

    // Add price filter
    if ($price_min = $request->get_param('price_min')) {
        $args['meta_query'][] = [
            'key' => '_price',
            'value' => $price_min,
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
    }
    if ($price_max = $request->get_param('price_max')) {
        $args['meta_query'][] = [
            'key' => '_price',
            'value' => $price_max,
            'compare' => '<=',
            'type' => 'NUMERIC'
        ];
    }

    // Add stock status filter
    if ($stock = $request->get_param('stock_status')) {
        $args['meta_query'][] = [
            'key' => '_stock_status',
            'value' => $stock,
            'compare' => '='
        ];
    }

    // Apply location-based filtering
    // ... existing location filtering logic ...

    $query = new WP_Query($args);
    return rest_ensure_response($query->posts);
}
```

### Step 3: Frontend Implementation

Create filter components for each category with the appropriate filter options as defined above.

---

## Database Updates Required

### Add Product Attributes

Run this script to add all attribute terms to the database:

```bash
# Use WP-CLI to import attributes
wp wc product_attribute create --name="Type" --slug="pa_type"
wp wc product_attribute create --name="Size" --slug="pa_size"
wp wc product_attribute create --name="Material" --slug="pa_material"
wp wc product_attribute create --name="Colour" --slug="pa_colour"
# ... etc for all attributes
```

---

## ✅ Implementation Status

### Completed Features

**Product Attributes System** ✅
- Created `LBP_Product_Attributes` class in `wp-content/plugins/location-based-products/includes/product-attributes.php`
- 11 product attributes with full term definitions
- Admin interface at Products → Filter Attributes
- One-click attribute and term creation

**Extended REST API** ✅
- Enhanced `/wp-json/lbp/v1/products` endpoint with filter support
- Support for all 11 attribute filters
- Price range filtering (min/max)
- Stock status filtering
- Multi-value filter support (comma-separated)
- Sorting options (date, price, title, popularity, rating)
- Search integration
- Location-based filtering compatibility

**Filter Parameters Available:**
- `type` - Product type
- `size` - Product size
- `material` - Material type
- `colour` - Color options
- `storage` - Storage type (beds)
- `headboard` - Headboard type (beds)
- `upholstery` - Upholstery material
- `brand` - Brand (mattresses)
- `mechanism` - Recliner mechanism
- `thickness` - Mattress thickness
- `discount_range` - Discount percentage range
- `price_min` / `price_max` - Price range
- `stock_status` - Inventory status
- `search` - Text search
- `orderby` / `order` - Sorting

### Quick Start

1. **Activate Attributes:**
   ```
   Admin → Products → Filter Attributes → Create/Update All Attributes
   ```

2. **Test Filter API:**
   ```bash
   curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&material=fabric&colour=beige"
   ```

3. **Complete Usage Guide:**
   See [FILTER-API-USAGE.md](./FILTER-API-USAGE.md) for detailed examples and frontend integration code.

---

**Source:** AF Website changes.xlsx
**Last Updated:** 2025-11-28
**Implementation Date:** 2025-11-28
