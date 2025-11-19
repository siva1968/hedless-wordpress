# New Features Implementation Guide

> **Version**: 1.1.0
> **Implementation Date**: 2025-11-19
> **Added Features**: Location-Based GST Calculation & Location-Based Sliders

---

## Overview

This guide documents the newly implemented features for the Location-Based Products plugin:

1. **Location-Based GST/Tax Calculation** - India-compliant GST calculation with REST API
2. **Location-Based Sliders/Banners** - Dynamic sliders for location-specific promotions

---

## Feature 1: Location-Based GST Calculation

### Purpose

Provides comprehensive GST (Goods and Services Tax) calculation for Indian e-commerce, supporting:
- **Intrastate transactions**: CGST + SGST
- **Interstate transactions**: IGST
- Location-specific tax rates
- HSN code management
- GSTIN validation

### Implementation Files

- **Core Logic**: `wp-content/plugins/location-based-products/includes/gst-integration.php`
- **Test Script**: `test-gst-api.php`

### REST API Endpoints

#### 1. Get GST Rates for Location

```
GET /wp-json/lbp/v1/gst/rates
```

**Parameters**:
- `location_id` (integer) - Location ID
- `postal_code` (string) - Alternative to location_id

**Response**:
```json
{
  "success": true,
  "location": {
    "id": 123,
    "name": "Mumbai Store",
    "state": "MH",
    "state_name": "Maharashtra"
  },
  "gst": {
    "type": "intrastate",
    "rate": 18,
    "cgst_rate": 9,
    "sgst_rate": 9,
    "igst_rate": 0,
    "hsn_code": "9403"
  }
}
```

#### 2. Calculate GST for Product

```
POST /wp-json/lbp/v1/gst/calculate
```

**Body**:
```json
{
  "product_id": 456,
  "location_id": 123,
  "quantity": 2,
  "price": 10000
}
```

**Response**:
```json
{
  "success": true,
  "product": {
    "id": 456,
    "name": "Wooden Sofa Set",
    "hsn_code": "9403"
  },
  "location": {
    "id": 123,
    "name": "Mumbai Store",
    "state": "MH"
  },
  "calculation": {
    "base_price": 20000,
    "quantity": 2,
    "gst_type": "intrastate",
    "cgst": 1800,
    "sgst": 1800,
    "cgst_rate": 9,
    "sgst_rate": 9,
    "total_gst": 3600,
    "total_amount": 23600
  }
}
```

#### 3. Validate GSTIN

```
POST /wp-json/lbp/v1/gst/validate-gstin
```

**Body**:
```json
{
  "gstin": "29ABCDE1234F1Z5"
}
```

**Response**:
```json
{
  "success": true,
  "gstin": "29ABCDE1234F1Z5",
  "valid": true,
  "details": {
    "state_code": "29",
    "pan": "ABCDE1234F",
    "format_valid": true
  }
}
```

#### 4. Get Tax Breakdown

```
GET /wp-json/lbp/v1/gst/breakdown
```

**Parameters**:
- `location_id` (required, integer)
- `product_id` (optional, integer)
- `cart_total` (optional, number)

**Response**:
```json
{
  "success": true,
  "breakdown": {
    "location": {
      "id": 123,
      "name": "Mumbai Store",
      "state": "MH",
      "state_name": "Maharashtra"
    },
    "gst_type": "intrastate",
    "rates": {
      "cgst": {
        "rate": 9,
        "label": "Central GST (CGST)",
        "description": "Collected by Central Government"
      },
      "sgst": {
        "rate": 9,
        "label": "State GST (SGST)",
        "description": "Collected by State Government"
      },
      "total_rate": 18
    },
    "calculation": {
      "subtotal": 50000,
      "gst_amount": 9000,
      "total": 59000
    }
  }
}
```

### Admin Configuration

#### Location GST Settings

When editing a location (`Location Products > Locations > Edit Location`):

**GST / Tax Settings Section**:

1. **GST Rate (%)**: Dropdown with common rates
   - 0% (Exempt)
   - 5%
   - 12%
   - 18% (Default for furniture)
   - 28% (Luxury items)

2. **GST Type**: How tax is calculated
   - **Intrastate (CGST + SGST)**: Within same state - tax split equally between Central and State
   - **Interstate (IGST)**: Between states - unified tax

3. **Default HSN Code**: Harmonized System of Nomenclature code
   - Default: `9403` (furniture)
   - Example: `9401` (seats), `9404` (mattresses)

### Frontend Integration

#### React/Next.js Example

```javascript
// Calculate GST for cart
async function calculateCartGST(locationId, cartItems) {
  const calculations = await Promise.all(
    cartItems.map(item =>
      fetch('/wp-json/lbp/v1/gst/calculate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          product_id: item.id,
          location_id: locationId,
          quantity: item.quantity,
          price: item.price
        })
      }).then(res => res.json())
    )
  );

  return calculations.reduce((acc, calc) => ({
    baseTotal: acc.baseTotal + calc.calculation.base_price,
    gstTotal: acc.gstTotal + calc.calculation.total_gst,
    grandTotal: acc.grandTotal + calc.calculation.total_amount
  }), { baseTotal: 0, gstTotal: 0, grandTotal: 0 });
}

// Display GST breakdown
function GSTBreakdown({ locationId, cartTotal }) {
  const [breakdown, setBreakdown] = useState(null);

  useEffect(() => {
    fetch(`/wp-json/lbp/v1/gst/breakdown?location_id=${locationId}&cart_total=${cartTotal}`)
      .then(res => res.json())
      .then(data => setBreakdown(data.breakdown));
  }, [locationId, cartTotal]);

  if (!breakdown) return null;

  return (
    <div className="gst-breakdown">
      <h3>Tax Breakdown</h3>
      <p>Location: {breakdown.location.name}, {breakdown.location.state_name}</p>
      {breakdown.gst_type === 'intrastate' ? (
        <>
          <p>CGST ({breakdown.rates.cgst.rate}%): ₹{breakdown.calculation.gst_amount / 2}</p>
          <p>SGST ({breakdown.rates.sgst.rate}%): ₹{breakdown.calculation.gst_amount / 2}</p>
        </>
      ) : (
        <p>IGST ({breakdown.rates.igst.rate}%): ₹{breakdown.calculation.gst_amount}</p>
      )}
      <p><strong>Total GST: ₹{breakdown.calculation.gst_amount}</strong></p>
    </div>
  );
}
```

### Testing

Run the test script:

```bash
php test-gst-api.php
```

This will test:
- Get GST rates for locations
- Calculate GST for products
- Validate GSTIN format
- Get tax breakdown
- Direct calculation examples

---

## Feature 2: Location-Based Sliders

### Purpose

Manage location-specific promotional sliders/banners with:
- Location assignment (all locations or specific locations)
- Scheduling (start/end dates)
- Priority ordering
- Complete customization (text, colors, images, buttons)
- REST API for headless frontends

### Implementation Files

- **Core Logic**: `wp-content/plugins/location-based-products/includes/slider-management.php`
- **Test Script**: `test-sliders-api.php`

### Custom Post Type

**Post Type**: `lbp_slider`
**Admin Location**: `Products > Sliders` in WordPress admin

### REST API Endpoints

#### 1. Get Sliders for Location

```
GET /wp-json/lbp/v1/sliders
```

**Parameters**:
- `location_id` (integer) - Filter by location
- `postal_code` (string) - Alternative to location_id
- `active_only` (boolean, default: true) - Show only active sliders
- `limit` (integer, default: 10) - Max number to return

**Response**:
```json
{
  "success": true,
  "sliders": [
    {
      "id": 789,
      "title": "Summer Sale 2025",
      "subtitle": "Up to 50% off on sofas",
      "description": "Limited time offer...",
      "image": {
        "id": 101,
        "url": "https://example.com/image.jpg",
        "thumbnail": "https://example.com/image-150x150.jpg",
        "medium": "https://example.com/image-300x300.jpg",
        "large": "https://example.com/image-1024x1024.jpg",
        "alt": "Summer sale banner"
      },
      "button": {
        "text": "Shop Now",
        "link": "https://example.com/shop/sofas",
        "target": "_self"
      },
      "style": {
        "text_position": "center",
        "text_color": "#ffffff",
        "overlay_opacity": 0.3
      },
      "priority": 10,
      "locations": [123, 456],
      "schedule": {
        "start_date": "2025-06-01T00:00",
        "end_date": "2025-08-31T23:59",
        "is_active": true
      }
    }
  ],
  "total": 1,
  "location_id": 123
}
```

#### 2. Get Single Slider

```
GET /wp-json/lbp/v1/sliders/{id}
```

**Response**:
```json
{
  "success": true,
  "slider": {
    "id": 789,
    "title": "Summer Sale 2025",
    // ... full slider data
  }
}
```

#### 3. Get Active Sliders (Convenience Endpoint)

```
GET /wp-json/lbp/v1/sliders/active
```

**Parameters**:
- `location_id` (integer, optional)

Returns only active, currently scheduled sliders.

### Admin Configuration

#### Creating a Slider

1. Go to **Products > Sliders > Add New Slider**

2. **Basic Information**:
   - **Title**: Main headline
   - **Content**: Description/body text
   - **Featured Image**: Banner image (recommended: 1920x600px)

3. **Slider Settings**:
   - **Subtitle**: Optional subtitle text
   - **Button Text**: CTA button text (e.g., "Shop Now")
   - **Button Link**: URL for the button
   - **Link Target**: Same window or new window
   - **Text Position**: Left, Center, or Right
   - **Text Color**: Color picker for text
   - **Overlay Opacity**: Background darkness (0-1)
   - **Priority**: Display order (higher = first, 1-100)

4. **Location Assignment**:
   - **All Locations**: Show everywhere
   - **Specific Locations**: Select which locations

5. **Schedule**:
   - **Active**: Enable/disable slider
   - **Start Date**: When to start showing (optional)
   - **End Date**: When to stop showing (optional)

6. **Publish** the slider

### Frontend Integration

#### React Component Example

```jsx
import { useState, useEffect } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';

function LocationSliders({ locationId }) {
  const [sliders, setSliders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`/wp-json/lbp/v1/sliders?location_id=${locationId}&active_only=true`)
      .then(res => res.json())
      .then(data => {
        setSliders(data.sliders || []);
        setLoading(false);
      });
  }, [locationId]);

  if (loading) return <div>Loading sliders...</div>;
  if (sliders.length === 0) return null;

  return (
    <Swiper
      spaceBetween={0}
      slidesPerView={1}
      loop={sliders.length > 1}
      autoplay={{ delay: 5000 }}
    >
      {sliders.map(slider => (
        <SwiperSlide key={slider.id}>
          <div
            className="slider-slide"
            style={{
              backgroundImage: slider.image ? `url(${slider.image.large})` : 'none',
              backgroundSize: 'cover',
              backgroundPosition: 'center',
              minHeight: '400px',
              position: 'relative'
            }}
          >
            <div
              className="slider-overlay"
              style={{
                position: 'absolute',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                backgroundColor: `rgba(0, 0, 0, ${slider.style.overlay_opacity})`,
                display: 'flex',
                alignItems: 'center',
                justifyContent: slider.style.text_position
              }}
            >
              <div
                className="slider-content"
                style={{
                  color: slider.style.text_color,
                  textAlign: slider.style.text_position,
                  padding: '2rem',
                  maxWidth: '800px'
                }}
              >
                <h2 className="slider-title">{slider.title}</h2>
                {slider.subtitle && (
                  <p className="slider-subtitle">{slider.subtitle}</p>
                )}
                {slider.description && (
                  <div className="slider-description"
                       dangerouslySetInnerHTML={{ __html: slider.description }} />
                )}
                {slider.button.text && (
                  <a
                    href={slider.button.link}
                    target={slider.button.target}
                    className="slider-button"
                    style={{
                      display: 'inline-block',
                      marginTop: '1rem',
                      padding: '0.75rem 2rem',
                      backgroundColor: slider.style.text_color,
                      color: '#000',
                      textDecoration: 'none',
                      borderRadius: '4px'
                    }}
                  >
                    {slider.button.text}
                  </a>
                )}
              </div>
            </div>
          </div>
        </SwiperSlide>
      ))}
    </Swiper>
  );
}

export default LocationSliders;
```

#### Next.js Server-Side Example

```jsx
// app/page.jsx
import SliderCarousel from '@/components/SliderCarousel';

async function getSliders(locationId) {
  const res = await fetch(
    `${process.env.WORDPRESS_URL}/wp-json/lbp/v1/sliders?location_id=${locationId}`,
    { next: { revalidate: 300 } } // Cache for 5 minutes
  );
  const data = await res.json();
  return data.sliders || [];
}

export default async function HomePage({ searchParams }) {
  const locationId = searchParams.location || 1;
  const sliders = await getSliders(locationId);

  return (
    <main>
      <SliderCarousel sliders={sliders} />
      {/* Rest of the page */}
    </main>
  );
}
```

### Testing

Run the test script:

```bash
php test-sliders-api.php
```

This will test:
- Get all sliders
- Get sliders for specific location
- Get active sliders only
- Get single slider details
- Show frontend integration examples

---

## Database Schema Changes

### Location Meta (Additional Fields)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_lbp_gst_rate` | float | GST percentage (0, 5, 12, 18, 28) |
| `_lbp_gst_type` | string | intrastate or interstate |
| `_lbp_default_hsn_code` | string | Default HSN code for products |

### Slider Post Type Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `_lbp_slider_subtitle` | string | Subtitle text |
| `_lbp_slider_button_text` | string | CTA button text |
| `_lbp_slider_button_link` | string | Button URL |
| `_lbp_slider_button_target` | string | _self or _blank |
| `_lbp_slider_text_position` | string | left, center, right |
| `_lbp_slider_text_color` | string | Hex color code |
| `_lbp_slider_overlay_opacity` | float | 0-1 |
| `_lbp_slider_priority` | integer | 1-100 |
| `_lbp_slider_type` | string | all or specific |
| `_lbp_slider_locations` | array | Location IDs |
| `_lbp_slider_is_active` | boolean | Active status |
| `_lbp_slider_start_date` | datetime | Start date |
| `_lbp_slider_end_date` | datetime | End date |

---

## Migration Notes

### Upgrading from v1.0.0 to v1.1.0

1. **No database migrations required** - New features use existing infrastructure

2. **Update plugin files**:
   - Replace `wp-content/plugins/location-based-products/` with new version
   - Or pull latest from Git

3. **Configure GST for existing locations**:
   - Edit each location
   - Set GST rate, type, and HSN code
   - Save location

4. **Create sliders** (optional):
   - Go to Products > Sliders
   - Create promotional banners
   - Assign to locations

5. **Test new endpoints**:
   ```bash
   php test-gst-api.php
   php test-sliders-api.php
   ```

---

## Support & Troubleshooting

### Common Issues

#### GST Calculations Incorrect

**Check**:
1. Location GST settings are configured
2. GST type matches the transaction (intrastate vs interstate)
3. Product has correct HSN code if overriding default

#### Sliders Not Showing

**Check**:
1. Slider is published and marked as active
2. Current date is within schedule (start/end dates)
3. Location assignment includes the requested location
4. Featured image is set for the slider

#### API Returns Empty Results

**Check**:
1. Location ID is valid and active
2. Products/sliders exist in the database
3. Permissions are correctly set (most endpoints are public)

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at `wp-content/debug.log`

---

## Changelog

### Version 1.1.0 (2025-11-19)

**Added**:
- ✨ Location-based GST calculation with full India compliance
- ✨ GST REST API endpoints (rates, calculate, validate, breakdown)
- ✨ GSTIN validation with format checking
- ✨ Location-based sliders/banners system
- ✨ Slider REST API endpoints with scheduling
- ✨ Admin interfaces for GST and slider configuration
- ✨ Comprehensive test scripts for new features
- 📚 Complete documentation for new features

**Updated**:
- Plugin version to 1.1.0
- Location meta box with GST fields
- Plugin description to include new features

---

## Future Enhancements

### Planned Features

1. **GST Reports**:
   - Monthly GST summary
   - Tax collection reports by location
   - Export for GST filing

2. **Advanced Slider Features**:
   - Animation effects
   - Video backgrounds
   - A/B testing support
   - Click tracking analytics

3. **Multi-currency Support**:
   - Location-based currency
   - Automatic conversion
   - GST in multiple currencies

---

## Credits

**Developer**: Siva
**Implementation Date**: November 19, 2025
**Plugin Version**: 1.1.0
**WordPress Compatibility**: 6.0+
**WooCommerce Compatibility**: 6.0+

---

For more information, see:
- `CLAUDE.md` - Complete codebase documentation
- `FUNCTIONALITY-VERIFICATION-REPORT.md` - Feature verification details
- `README.md` - Plugin overview
