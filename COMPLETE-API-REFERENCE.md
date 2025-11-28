# Complete REST API Reference

**Last Updated:** 2025-11-28
**Version:** 1.0.0
**Base URL:** `/wp-json/lbp/v1`

---

## Table of Contents

1. [Location APIs](#location-apis)
2. [Product Catalog & Filtering](#product-catalog--filtering)
3. [Product Sliders](#product-sliders)
4. [Single Product](#single-product)
5. [Categories](#categories)
6. [Home Page Content](#home-page-content)
7. [Product Availability](#product-availability)

---

## Location APIs

### 1. Detect User Location
```
GET /lbp/v1/detect-location
```

**Description:** Auto-detect user location based on IP, postal code, or GPS coordinates.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `ip` | string | IP address (optional, auto-detected if not provided) |
| `postal_code` | string | Postal/ZIP code |
| `lat` | number | Latitude |
| `lng` | number | Longitude |

**Response:**
```json
{
  "success": true,
  "location": {
    "id": 123,
    "name": "Mumbai Store",
    "address": "Bandra West, Mumbai",
    "latitude": "19.0596",
    "longitude": "72.8295",
    "postal_codes": "400050,400051,400052"
  },
  "detection_method": "postal_code",
  "timestamp": 1234567890
}
```

### 2. Set User Location
```
POST /lbp/v1/set-location
```

**Description:** Manually set user's location.

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `location_id` | integer | Yes | Location ID to set |

**Response:**
```json
{
  "success": true,
  "message": "Location set successfully",
  "location": { ... }
}
```

### 3. Get All Locations ✅
```
GET /lbp/v1/locations
```

**Description:** Get list of all available locations.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `active_only` | boolean | true | Only return active locations |
| `search` | string | - | Search by location name or postal code |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/locations?active_only=true"
```

**Response:**
```json
{
  "success": true,
  "locations": [
    {
      "id": 123,
      "name": "Mumbai Store",
      "address": "Bandra West, Mumbai",
      "latitude": "19.0596",
      "longitude": "72.8295",
      "postal_codes": "400050,400051,400052"
    }
  ],
  "total": 5
}
```

---

## Product Catalog & Filtering

### 1. Get Products with Filters
```
GET /lbp/v1/products
```

**Description:** Get products with comprehensive filtering, sorting, and location-based availability.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | integer | - | Filter by location |
| `postal_code` | string | - | Filter by postal code |
| `category` | string | - | Category slug or ID |
| `per_page` | integer | 10 | Products per page (max 100) |
| `page` | integer | 1 | Page number |
| `orderby` | string | date | Sort by: date, title, price, popularity, rating |
| `order` | string | desc | Order: asc, desc |
| `search` | string | - | Search query |

**Filter Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Product type (comma-separated) |
| `size` | string | Size (comma-separated) |
| `material` | string | Material (comma-separated) |
| `colour` | string | Colour (comma-separated) |
| `storage` | string | Storage type |
| `headboard` | string | Headboard type |
| `upholstery` | string | Upholstery material |
| `brand` | string | Brand (comma-separated) |
| `mechanism` | string | Recliner mechanism |
| `thickness` | string | Mattress thickness |
| `discount_range` | string | Discount range |
| `price_min` | number | Minimum price |
| `price_max` | number | Maximum price |
| `stock_status` | string | instock, outofstock, onbackorder |

**Examples:**
```bash
# Basic category filter
GET /lbp/v1/products?category=sofas&per_page=20

# Multiple filters
GET /lbp/v1/products?category=sofas&material=fabric,leather&colour=beige&size=321

# Price range
GET /lbp/v1/products?category=beds&price_min=20000&price_max=50000

# With location
GET /lbp/v1/products?location_id=123&category=mattress&brand=kurlon&stock_status=instock

# Search with filters
GET /lbp/v1/products?search=luxury&category=sofas&material=leather
```

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 456,
      "name": "Luxury Fabric Sofa",
      "slug": "luxury-fabric-sofa",
      "permalink": "https://example.com/product/luxury-fabric-sofa",
      "price": "45000",
      "regular_price": "55000",
      "sale_price": "45000",
      "on_sale": true,
      "stock_status": "instock",
      "featured": true,
      "categories": [...],
      "images": [...],
      "attributes": [...],
      "average_rating": "4.5",
      "rating_count": 24
    }
  ],
  "location": { ... },
  "filters": {
    "material": "fabric",
    "colour": "beige",
    "price_range": { "min": 20000, "max": 50000 }
  },
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 8
  }
}
```

---

## Product Sliders

### 1. Featured Products ✨
```
GET /lbp/v1/products/featured
```

**Description:** Get featured/highlighted products.

**Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `per_page` | integer | 10 (max 50) |
| `page` | integer | 1 |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products/featured?per_page=10"
```

### 2. New Arrivals 🆕
```
GET /lbp/v1/products/new-arrivals
```

**Description:** Get recently added products.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | integer | - | Filter by location |
| `days` | integer | 30 | Products added in last N days |
| `per_page` | integer | 10 | Max 50 |
| `page` | integer | 1 | Page number |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products/new-arrivals?days=7&per_page=12"
```

### 3. Best Sellers 🏆
```
GET /lbp/v1/products/best-sellers
```

**Description:** Get products with highest sales.

**Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `per_page` | integer | 10 (max 50) |
| `page` | integer | 1 |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products/best-sellers?per_page=8"
```

### 4. On Sale Products 🔥
```
GET /lbp/v1/products/on-sale
```

**Description:** Get products currently on sale/discount.

**Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `per_page` | integer | 10 (max 50) |
| `page` | integer | 1 |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products/on-sale?per_page=15"
```

### 5. Trending Products 📈
```
GET /lbp/v1/products/trending
```

**Description:** Get trending products (recent high sales + views).

**Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `per_page` | integer | 10 (max 50) |

### 6. Related Products
```
GET /lbp/v1/products/related/{product_id}
```

**Description:** Get products related to a specific product.

**Path Parameters:**
| Parameter | Type | Required |
|-----------|------|----------|
| `product_id` | integer | Yes |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `per_page` | integer | 4 (max 20) |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/products/related/456?per_page=6"
```

**Slider Response Format:**
```json
{
  "success": true,
  "title": "Featured Products",
  "products": [...],
  "total": 45,
  "pagination": {
    "per_page": 10,
    "current_page": 1,
    "total_pages": 5
  }
}
```

---

## Single Product

### 1. Get Product by ID
```
GET /lbp/v1/product/{id}
```

**Description:** Get complete product details by ID.

**Path Parameters:**
| Parameter | Type | Required |
|-----------|------|----------|
| `id` | integer | Yes |

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `location_id` | integer | Get location-specific data |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/product/456?location_id=123"
```

### 2. Get Product by Slug
```
GET /lbp/v1/product/slug/{slug}
```

**Description:** Get complete product details by slug.

**Path Parameters:**
| Parameter | Type | Required |
|-----------|------|----------|
| `slug` | string | Yes |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/product/slug/luxury-fabric-sofa"
```

**Single Product Response:**
```json
{
  "success": true,
  "product": {
    "id": 456,
    "name": "Luxury Fabric Sofa",
    "slug": "luxury-fabric-sofa",
    "permalink": "https://example.com/product/luxury-fabric-sofa",
    "type": "simple",
    "status": "publish",
    "featured": true,
    "description": "Full product description...",
    "short_description": "Short description...",
    "sku": "LFS-001",
    "price": "45000",
    "regular_price": "55000",
    "sale_price": "45000",
    "on_sale": true,
    "stock_status": "instock",
    "stock_quantity": 15,
    "manage_stock": true,
    "average_rating": "4.5",
    "rating_count": 24,
    "categories": [
      {
        "id": 12,
        "name": "Sofas",
        "slug": "sofas"
      }
    ],
    "tags": [...],
    "images": [
      {
        "id": 789,
        "src": "https://example.com/image.jpg",
        "thumbnail": "https://example.com/image-150x150.jpg",
        "medium": "https://example.com/image-300x300.jpg",
        "large": "https://example.com/image-1024x1024.jpg",
        "alt": "Luxury Fabric Sofa",
        "name": "Product Image",
        "position": 0
      }
    ],
    "attributes": [
      {
        "id": 1,
        "name": "Material",
        "slug": "pa_material",
        "visible": true,
        "variation": false,
        "options": [
          {
            "id": 10,
            "name": "Fabric",
            "slug": "fabric"
          }
        ]
      }
    ],
    "variations": [],
    "date_created": "2025-01-15 10:30:00",
    "date_modified": "2025-01-20 14:45:00",
    "location_specific": {
      "location_id": 123,
      "available": true,
      "delivery_options": ["standard", "express"]
    }
  }
}
```

---

## Categories

### 1. Get All Categories
```
GET /lbp/v1/categories
```

**Description:** Get all product categories with images and metadata.

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `parent` | integer | - | Get child categories of specific parent |
| `hide_empty` | boolean | false | Hide categories with no products |
| `per_page` | integer | 100 | Max number to return |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/categories?hide_empty=true"
```

### 2. Get Home Screen Categories
```
GET /lbp/v1/categories/home
```

**Description:** Get the 12 main categories for home screen (2-column slideable layout).

**Response:**
```json
{
  "success": true,
  "categories": [
    {
      "id": 1,
      "name": "AF Luxe",
      "slug": "af-luxe",
      "description": "Luxury furniture collection",
      "count": 45,
      "parent": 0,
      "image": {
        "src": "https://example.com/category.jpg",
        "thumbnail": "https://example.com/category-150x150.jpg",
        "alt": "AF Luxe Category"
      },
      "link": "https://example.com/product-category/af-luxe"
    },
    {
      "id": 2,
      "name": "Sofa Sets",
      "slug": "sofa-sets",
      ...
    }
    // ... 10 more categories
  ],
  "total": 12,
  "display_format": "2 columns, slideable"
}
```

### 3. Get Category with Products
```
GET /lbp/v1/category/{slug}
```

**Description:** Get category details along with its products.

**Path Parameters:**
| Parameter | Type | Required |
|-----------|------|----------|
| `slug` | string | Yes |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `per_page` | integer | 12 (max 100) |
| `page` | integer | 1 |
| `orderby` | string | date |
| `order` | string | desc |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/category/sofas?per_page=20&orderby=price&order=asc"
```

**Response:**
```json
{
  "success": true,
  "category": {
    "id": 12,
    "name": "Sofas",
    "slug": "sofas",
    "description": "Explore our sofa collection",
    "count": 150,
    "image": {...}
  },
  "products": [...],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 8
  }
}
```

---

## Home Page Content

### 1. Get Home Sections
```
GET /lbp/v1/home-sections
```

**Description:** Get all home page sections configuration.

**Response:**
```json
{
  "success": true,
  "sections": [
    {
      "id": "hero",
      "type": "banner",
      "title": "Welcome to Our Store",
      "enabled": true,
      "order": 1
    },
    {
      "id": "categories",
      "type": "category_slider",
      "title": "Shop by Category",
      "columns": 2,
      "slideable": true,
      "enabled": true,
      "order": 2
    },
    {
      "id": "featured",
      "type": "product_slider",
      "title": "Featured Products",
      "endpoint": "/lbp/v1/products/featured",
      "enabled": true,
      "order": 3
    },
    {
      "id": "new_arrivals",
      "type": "product_slider",
      "title": "New Arrivals",
      "endpoint": "/lbp/v1/products/new-arrivals",
      "enabled": true,
      "order": 4
    }
  ]
}
```

### 2. Get Banners
```
GET /lbp/v1/banners
```

**Description:** Get promotional banners/sliders.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `position` | string | Filter by position: hero, sidebar, footer |
| `active_only` | boolean | Only active banners (default: true) |

### 3. Get Promotions
```
GET /lbp/v1/promotions
```

**Description:** Get active promotions.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `active_only` | boolean | Only active promotions (default: true) |

---

## Product Availability

### 1. Check Product Availability
```
GET /lbp/v1/check-availability/{product_id}
```

**Description:** Check if product is available at a location with specific quantity.

**Path Parameters:**
| Parameter | Type | Required |
|-----------|------|----------|
| `product_id` | integer | Yes |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| `location_id` | integer | - |
| `postal_code` | string | - |
| `quantity` | integer | 1 |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/check-availability/456?location_id=123&quantity=2"
```

**Response:**
```json
{
  "success": true,
  "available": true,
  "stock_quantity": 15,
  "stock_status": "instock",
  "price": "45000",
  "delivery_options": ["standard", "express"],
  "location": {...},
  "messages": []
}
```

### 2. Get Delivery Options
```
GET /lbp/v1/delivery-options
```

**Description:** Get available delivery options for a location and cart total.

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `location_id` | integer | Location ID |
| `postal_code` | string | Postal code |
| `cart_total` | number | Cart total amount |

**Example:**
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/delivery-options?location_id=123&cart_total=25000"
```

**Response:**
```json
{
  "success": true,
  "location": {...},
  "delivery_options": [
    {
      "type": "standard",
      "name": "Standard Delivery",
      "description": "5-7 business days",
      "fee": 0,
      "currency": "INR"
    },
    {
      "type": "express",
      "name": "Express Delivery",
      "description": "2-3 business days",
      "fee": 500,
      "currency": "INR"
    },
    {
      "type": "pickup",
      "name": "Store Pickup",
      "description": "Pickup from store",
      "fee": 0,
      "currency": "INR"
    }
  ],
  "cart_total": 25000
}
```

---

## Summary of All Endpoints

### Location Management (3 endpoints)
- ✅ `GET /detect-location` - Auto-detect location
- ✅ `POST /set-location` - Set location
- ✅ `GET /locations` - Get all locations

### Product Catalog (1 endpoint)
- ✅ `GET /products` - Get/filter products

### Product Sliders (6 endpoints)
- ✅ `GET /products/featured` - Featured products
- ✅ `GET /products/new-arrivals` - New arrivals
- ✅ `GET /products/best-sellers` - Best sellers
- ✅ `GET /products/on-sale` - Sale products
- ✅ `GET /products/trending` - Trending products
- ✅ `GET /products/related/{id}` - Related products

### Single Product (2 endpoints)
- ✅ `GET /product/{id}` - Get by ID
- ✅ `GET /product/slug/{slug}` - Get by slug

### Categories (3 endpoints)
- ✅ `GET /categories` - All categories
- ✅ `GET /categories/home` - Home categories
- ✅ `GET /category/{slug}` - Category with products

### Home Content (3 endpoints)
- ✅ `GET /home-sections` - Home sections config
- ✅ `GET /banners` - Promotional banners
- ✅ `GET /promotions` - Active promotions

### Availability (2 endpoints)
- ✅ `GET /check-availability/{id}` - Check availability
- ✅ `GET /delivery-options` - Delivery options

**Total: 20 REST API Endpoints** ✅

---

## Frontend Integration Example

```javascript
// Fetch home page data
async function fetchHomePage() {
  // 1. Detect location
  const location = await fetch('/wp-json/lbp/v1/detect-location').then(r => r.json());

  // 2. Get home sections
  const sections = await fetch('/wp-json/lbp/v1/home-sections').then(r => r.json());

  // 3. Get home categories
  const categories = await fetch('/wp-json/lbp/v1/categories/home').then(r => r.json());

  // 4. Get featured products
  const featured = await fetch(`/wp-json/lbp/v1/products/featured?location_id=${location.location.id}`).then(r => r.json());

  // 5. Get new arrivals
  const newArrivals = await fetch(`/wp-json/lbp/v1/products/new-arrivals?location_id=${location.location.id}`).then(r => r.json());

  // 6. Get sale products
  const onSale = await fetch(`/wp-json/lbp/v1/products/on-sale?location_id=${location.location.id}`).then(r => r.json());

  return {
    location: location.location,
    sections: sections.sections,
    categories: categories.categories,
    sliders: {
      featured: featured.products,
      newArrivals: newArrivals.products,
      onSale: onSale.products
    }
  };
}
```

---

**End of API Reference**
