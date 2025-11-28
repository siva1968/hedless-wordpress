# Product Filter API - Usage Guide

Complete guide for using the product filtering API with location-based products.

## Setup Instructions

### 1. Activate Product Attributes

After activating the plugin, navigate to:
```
WordPress Admin → Products → Filter Attributes
```

Click **"Create/Update All Attributes"** to set up all product attributes and terms.

This will create 11 product attributes with their respective terms based on AF Website specifications.

### 2. Assign Attributes to Products

Edit your WooCommerce products and assign the appropriate attribute values:

1. Go to **Products** → Edit any product
2. Scroll to **Product Data** → **Attributes** tab
3. Select attributes from the dropdown (pa_type, pa_size, pa_material, etc.)
4. Add values and click "Save attributes"
5. Publish the product

## API Endpoints

### Base URL
```
/wp-json/lbp/v1/products
```

## Filter Examples

### 1. Basic Category Filter

Get all sofas:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas"
```

### 2. Single Attribute Filter

Get all corner sofas:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&type=corner-sofas"
```

### 3. Multiple Attribute Filters

Get fabric corner sofas in beige color:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&type=corner-sofas&material=fabric&colour=beige"
```

### 4. Multiple Values for Same Attribute

Get sofas that are either fabric OR vegan leather:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&material=fabric,vegan-leather"
```

### 5. Size Filter

Get 3+2+1 sofa sets:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&size=321"
```

Note: Size values are slugified, so "3+2+1" becomes "321"

### 6. Price Range Filter

Get sofas between ₹20,000 and ₹50,000:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&price_min=20000&price_max=50000"
```

### 7. Stock Status Filter

Get only in-stock products:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&stock_status=instock"
```

### 8. Discount Range Filter

Get products with 16-35% discount:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&discount_range=16-35"
```

### 9. Sorting Options

Sort by price (low to high):
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&orderby=price&order=asc"
```

Sort by date (newest first):
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&orderby=date&order=desc"
```

Available sort options:
- `orderby`: date, title, price, popularity, rating
- `order`: asc, desc

### 10. Search + Filters

Search for "luxury" in sofas with specific filters:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&search=luxury&material=leather&colour=brown"
```

### 11. Pagination

Get page 2 with 20 products per page:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&per_page=20&page=2"
```

### 12. Location-Based Filtering

Combine location filtering with product filters:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?location_id=123&category=sofas&material=fabric&colour=beige"
```

## Category-Specific Filter Examples

### Recliners
```bash
# Motorized recliner sets in fabric
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=recliners&type=motorised-recliner-sets&mechanism=motorised&material=fabric"
```

### Tables
```bash
# Marble centre tables, 3ft size
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=tables&type=centre-tables&material=marble&size=3ft"
```

### Chairs
```bash
# Accent chairs in fabric or leather
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=chairs&type=accent-chairs&material=fabric,leather"
```

### Beds
```bash
# King size upholstered beds with storage
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=beds&type=upholstered-beds&size=king-6ft-beds&storage=with-storage"
```

### Mattress
```bash
# Kurlon or Sleepwell memory foam mattresses, 6-8 inch thickness
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=mattress&type=memory-foam-mattress&brand=kurlon,sleepwell&thickness=6-inch,8-inch"
```

## Advanced Filtering Examples

### 1. Complex Multi-Filter Query

Get fabric or leather sofas in brown or beige, sized 3+2+1 or 3+2, price between ₹30k-₹60k, in stock only:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?\
category=sofas&\
material=fabric,leather&\
colour=brown,beige&\
size=321,32&\
price_min=30000&\
price_max=60000&\
stock_status=instock&\
orderby=price&\
order=asc"
```

### 2. With Location Detection

Auto-detect location and filter products:
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?\
postal_code=400050&\
category=mattress&\
brand=kurlon&\
thickness=6-inch&\
price_max=25000"
```

## API Response Format

```json
{
  "success": true,
  "products": [
    {
      "id": 123,
      "name": "Luxury Fabric Sofa",
      "price": "45000",
      "regular_price": "55000",
      "sale_price": "45000",
      "stock_status": "instock",
      "stock_quantity": 10,
      "attributes": {
        "type": "Sofa sets",
        "size": "3+2+1",
        "material": "Fabric",
        "colour": "Beige"
      },
      "images": [...],
      "permalink": "..."
    }
  ],
  "location": {
    "id": 123,
    "name": "Mumbai Store",
    "address": "...",
    ...
  },
  "filters": {
    "material": "fabric",
    "colour": "beige",
    "price_range": {
      "min": 20000,
      "max": 50000
    }
  },
  "pagination": {
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 5
  }
}
```

## Frontend Integration Example

### React/Next.js Component

```javascript
import { useState, useEffect } from 'react';

function ProductFilters() {
  const [products, setProducts] = useState([]);
  const [filters, setFilters] = useState({
    category: 'sofas',
    material: [],
    colour: [],
    size: [],
    price_min: null,
    price_max: null
  });

  const fetchProducts = async () => {
    const params = new URLSearchParams();
    params.append('category', filters.category);

    // Add array filters
    if (filters.material.length) {
      params.append('material', filters.material.join(','));
    }
    if (filters.colour.length) {
      params.append('colour', filters.colour.join(','));
    }
    if (filters.size.length) {
      params.append('size', filters.size.join(','));
    }

    // Add price filters
    if (filters.price_min) params.append('price_min', filters.price_min);
    if (filters.price_max) params.append('price_max', filters.price_max);

    const response = await fetch(
      `/wp-json/lbp/v1/products?${params.toString()}`
    );
    const data = await response.json();
    setProducts(data.products);
  };

  useEffect(() => {
    fetchProducts();
  }, [filters]);

  const toggleFilter = (filterType, value) => {
    setFilters(prev => ({
      ...prev,
      [filterType]: prev[filterType].includes(value)
        ? prev[filterType].filter(v => v !== value)
        : [...prev[filterType], value]
    }));
  };

  return (
    <div>
      {/* Filter UI */}
      <div className="filters">
        <h3>Material</h3>
        {['fabric', 'leather', 'vegan-leather'].map(material => (
          <label key={material}>
            <input
              type="checkbox"
              checked={filters.material.includes(material)}
              onChange={() => toggleFilter('material', material)}
            />
            {material}
          </label>
        ))}

        {/* More filter options... */}
      </div>

      {/* Products Grid */}
      <div className="products-grid">
        {products.map(product => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </div>
  );
}
```

## Filter URL Slug Reference

When constructing filter URLs, use these slug formats:

### Type Slugs
- "Sofa sets" → `sofa-sets`
- "Corner Sofas" → `corner-sofas`
- "3+2+1" (size) → `321`
- "King / 6FT Beds" → `king-6ft-beds`

### General Rule
- Convert to lowercase
- Replace spaces with hyphens
- Remove special characters (except hyphens)
- "Memory Foam Mattress" → `memory-foam-mattress`

## Testing the API

### 1. Test Attribute Creation

Visit:
```
http://localhost/hedless/wp-admin/edit.php?post_type=product&page=lbp-product-attributes
```

Click "Create/Update All Attributes" and verify success message.

### 2. Verify Attributes Exist

```bash
# Check if attributes are registered
curl "http://localhost/hedless/wp-json/wc/v3/products/attributes" \
  -u consumer_key:consumer_secret
```

### 3. Test Basic Filter

```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?category=sofas&material=fabric"
```

### 4. Test Multiple Filters

```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products?\
category=sofas&\
material=fabric&\
colour=beige&\
size=321&\
price_min=20000&\
price_max=50000"
```

## Troubleshooting

### No Products Returned

1. **Check if attributes are created:**
   - Go to Products → Filter Attributes
   - Verify all attributes show "✓ Created"

2. **Verify products have attributes assigned:**
   - Edit a product
   - Check Product Data → Attributes tab
   - Ensure attributes are added and values selected

3. **Check filter values are correct:**
   - Use lowercase slugs (e.g., `corner-sofas` not `Corner Sofas`)
   - For multi-word terms, use hyphens

4. **Enable WordPress debug mode:**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### Filters Not Working

1. **Flush rewrite rules:**
   - Go to Settings → Permalinks
   - Click "Save Changes" (no modifications needed)

2. **Clear caches:**
   - Clear WordPress object cache
   - Clear any frontend caching (Redis, Memcached)

3. **Check taxonomy registration:**
   ```bash
   # Verify taxonomies are registered
   curl "http://localhost/hedless/wp-json/wp/v2/taxonomies"
   ```

## Performance Optimization

For large product catalogs:

1. **Add indexes** to post meta tables for price queries
2. **Enable object caching** (Redis/Memcached)
3. **Limit per_page** to reasonable numbers (10-50)
4. **Use pagination** instead of loading all results
5. **Consider ElasticSearch** for complex filtering needs

---

**Created:** 2025-11-28
**Plugin Version:** 1.0.0
**Compatibility:** WordPress 6.0+, WooCommerce 6.0+
