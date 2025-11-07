/**
 * Location-Based Products Admin JavaScript
 * Handles admin interface interactions and AJAX operations
 */

jQuery(document).ready(function($) {
    
    // Global variables
    let currentLocationId = 0;
    const modal = $('#location-modal');
    const form = $('#location-form');
    
    // Initialize admin functionality
    initLocationManagement();
    initTestFunctionality();
    initProductManagement();
    initImportExport();
    
    /**
     * Location Management Functions
     */
    function initLocationManagement() {
        // Open modal for new location
        $(document).on('click', '#add-new-location, #add-first-location', function(e) {
            e.preventDefault();
            openLocationModal();
        });
        
        // Edit location
        $(document).on('click', '.edit-location', function(e) {
            e.preventDefault();
            const locationId = $(this).data('location-id');
            editLocation(locationId);
        });
        
        // Delete location
        $(document).on('click', '.delete-location', function(e) {
            e.preventDefault();
            const locationId = $(this).data('location-id');
            deleteLocation(locationId);
        });
        
        // Close modal
        $(document).on('click', '.close, #cancel-location', function() {
            closeLocationModal();
        });
        
        // Close modal on outside click
        $(document).on('click', '.lbp-modal', function(e) {
            if (e.target === this) {
                closeLocationModal();
            }
        });
        
        // Save location form
        form.on('submit', function(e) {
            e.preventDefault();
            saveLocation();
        });
    }
    
    function openLocationModal(locationData = null) {
        currentLocationId = locationData ? locationData.id : 0;
        
        // Reset form
        form[0].reset();
        $('#location-id').val(currentLocationId);
        
        // Set title
        $('#modal-title').text(currentLocationId ? 'Edit Location' : 'Add New Location');
        
        // Fill form if editing
        if (locationData) {
            fillLocationForm(locationData);
        }
        
        modal.show();
    }
    
    function closeLocationModal() {
        modal.hide();
        currentLocationId = 0;
        form[0].reset();
    }
    
    function fillLocationForm(data) {
        $('#location-name').val(data.name || '');
        $('#location-description').val(data.description || '');
        $('#location-address').val(data.address || '');
        $('#location-city').val(data.city || '');
        $('#location-state').val(data.state || '');
        $('#location-country').val(data.country || '');
        $('#location-postal-codes').val(data.postal_codes || '');
        $('#location-latitude').val(data.latitude || '');
        $('#location-longitude').val(data.longitude || '');
        $('#location-delivery-radius').val(data.delivery_radius || '');
        $('#location-priority').val(data.priority || '0');
        $('#location-active').prop('checked', data.is_active === '1');
    }
    
    function editLocation(locationId) {
        showLoading('Loading location data...');
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lbp_get_location_data',
                location_id: locationId,
                nonce: lbp_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    openLocationModal(response.data);
                } else {
                    showNotification('Failed to load location data.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('An error occurred while loading location data.', 'error');
            }
        });
    }
    
    function saveLocation() {
        const formData = form.serialize();
        const submitButton = $('#save-location');
        
        submitButton.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=lbp_save_location&nonce=' + lbp_ajax.nonce,
            success: function(response) {
                submitButton.prop('disabled', false).text('Save Location');
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    closeLocationModal();
                    location.reload(); // Refresh the page to show updated data
                } else {
                    showNotification(response.data.message || 'Failed to save location.', 'error');
                }
            },
            error: function() {
                submitButton.prop('disabled', false).text('Save Location');
                showNotification('An error occurred while saving the location.', 'error');
            }
        });
    }
    
    function deleteLocation(locationId) {
        if (!confirm(lbp_ajax.messages.confirm_delete)) {
            return;
        }
        
        showLoading('Deleting location...');
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lbp_delete_location',
                location_id: locationId,
                nonce: lbp_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('.location-card[data-location-id="' + locationId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data.message || 'Failed to delete location.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('An error occurred while deleting the location.', 'error');
            }
        });
    }
    
    /**
     * Test Functionality
     */
    function initTestFunctionality() {
        // Test IP address
        $('#test-ip-form').on('submit', function(e) {
            e.preventDefault();
            const ip = $('#test-ip').val().trim();
            if (!ip) return;
            
            testLocationDetection('ip', { ip: ip }, '#ip-result');
        });
        
        // Test postal code
        $('#test-postal-form').on('submit', function(e) {
            e.preventDefault();
            const postal = $('#test-postal').val().trim();
            if (!postal) return;
            
            testLocationDetection('postal', { postal: postal }, '#postal-result');
        });
        
        // Test coordinates
        $('#test-coordinates-form').on('submit', function(e) {
            e.preventDefault();
            const latitude = $('#test-latitude').val().trim();
            const longitude = $('#test-longitude').val().trim();
            if (!latitude || !longitude) return;
            
            testLocationDetection('coordinates', { latitude: latitude, longitude: longitude }, '#coordinates-result');
        });
        
        // Test city
        $('#test-city-form').on('submit', function(e) {
            e.preventDefault();
            const city = $('#test-city').val().trim();
            const region = $('#test-region').val().trim();
            if (!city) return;
            
            testLocationDetection('city', { city: city, region: region }, '#city-result');
        });
    }
    
    function testLocationDetection(method, data, resultContainer) {
        const container = $(resultContainer);
        container.removeClass('success error info').addClass('info').show()
            .html('<span class="spinner"></span> Testing location detection...');
        
        data.method = method;
        data.action = 'lbp_test_location_detection';
        data.nonce = lbp_ajax.nonce;
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    if (response.data.message) {
                        container.removeClass('info').addClass('error')
                            .html('<strong>No Match:</strong> ' + response.data.message);
                    } else {
                        const location = response.data;
                        const html = `
                            <h4>Location Found: ${location.name}</h4>
                            <p><strong>Address:</strong> ${location.address || 'N/A'}</p>
                            <p><strong>City:</strong> ${location.city || 'N/A'}</p>
                            <p><strong>State:</strong> ${location.state || 'N/A'}</p>
                            <p><strong>Postal Codes:</strong> ${location.postal_codes || 'N/A'}</p>
                            <p><strong>Coordinates:</strong> ${location.latitude || 'N/A'}, ${location.longitude || 'N/A'}</p>
                            <p><strong>Delivery Radius:</strong> ${location.delivery_radius || 'N/A'} km</p>
                            <p><strong>Priority:</strong> ${location.priority || '0'}</p>
                            <p><strong>Status:</strong> ${location.is_active === '1' ? 'Active' : 'Inactive'}</p>
                        `;
                        container.removeClass('info').addClass('success').html(html);
                    }
                } else {
                    container.removeClass('info').addClass('error')
                        .html('<strong>Error:</strong> ' + (response.data.message || 'Unknown error occurred.'));
                }
            },
            error: function() {
                container.removeClass('info').addClass('error')
                    .html('<strong>Error:</strong> Failed to test location detection.');
            }
        });
    }
    
    /**
     * Product Management Functions
     */
    function initProductManagement() {
        // Select all products
        $('#select-all-products').on('change', function() {
            $('input[name="product_ids[]"]').prop('checked', $(this).is(':checked'));
        });
        
        // Apply bulk action
        $('#apply-bulk-action').on('click', function() {
            const action = $('#bulk-action-type').val();
            const selectedProducts = $('input[name="product_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            
            if (selectedProducts.length === 0) {
                alert('Please select at least one product.');
                return;
            }
            
            applyBulkAction(action, selectedProducts);
        });
    }
    
    function applyBulkAction(action, productIds) {
        showLoading('Applying bulk action...');
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lbp_bulk_update_products',
                bulk_action: action,
                product_ids: productIds,
                nonce: lbp_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotification('Bulk action applied successfully!', 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Failed to apply bulk action.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('An error occurred while applying bulk action.', 'error');
            }
        });
    }
    
    /**
     * Import/Export Functions
     */
    function initImportExport() {
        $('#import-form').on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#import-file')[0];
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file to import.');
                return;
            }
            
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a valid CSV file.');
                return;
            }
            
            importLocations(file);
        });
    }
    
    function importLocations(file) {
        const formData = new FormData();
        formData.append('action', 'lbp_import_locations');
        formData.append('import_file', file);
        formData.append('nonce', lbp_ajax.nonce);
        
        const resultContainer = $('#import-result');
        resultContainer.removeClass('success error').addClass('info').show()
            .html('<span class="spinner"></span> Importing locations...');
        
        $.ajax({
            url: lbp_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    resultContainer.removeClass('info').addClass('success')
                        .html('<strong>Success:</strong> ' + response.data.message);
                } else {
                    resultContainer.removeClass('info').addClass('error')
                        .html('<strong>Error:</strong> ' + (response.data.message || 'Import failed.'));
                }
            },
            error: function() {
                resultContainer.removeClass('info').addClass('error')
                    .html('<strong>Error:</strong> Failed to import locations.');
            }
        });
    }
    
    /**
     * Utility Functions
     */
    function showLoading(message = 'Loading...') {
        if ($('.lbp-loading-overlay').length === 0) {
            $('body').append('<div class="lbp-loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; justify-content: center; align-items: center; color: white; font-size: 16px;"><span class="spinner" style="margin-right: 10px;"></span>' + message + '</div>');
        }
    }
    
    function hideLoading() {
        $('.lbp-loading-overlay').remove();
    }
    
    function showNotification(message, type = 'info') {
        const notification = $('<div class="lbp-notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 5000);
    }
    
    /**
     * Form Validation
     */
    function validateLocationForm() {
        const requiredFields = ['location-name', 'location-address'];
        let isValid = true;
        
        requiredFields.forEach(function(fieldId) {
            const field = $('#' + fieldId);
            const value = field.val().trim();
            
            if (!value) {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });
        
        // Validate coordinates if provided
        const lat = $('#location-latitude').val();
        const lng = $('#location-longitude').val();
        
        if (lat && (lat < -90 || lat > 90)) {
            $('#location-latitude').addClass('error');
            isValid = false;
        }
        
        if (lng && (lng < -180 || lng > 180)) {
            $('#location-longitude').addClass('error');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Add validation styling
    $('input, textarea, select').on('input change', function() {
        $(this).removeClass('error');
    });
    
    /**
     * Enhanced UI Features
     */
    
    // Auto-complete postal codes (basic implementation)
    $('#location-postal-codes').on('input', function() {
        let value = $(this).val();
        // Remove any non-alphanumeric characters except commas and spaces
        value = value.replace(/[^a-zA-Z0-9,\s]/g, '');
        // Format with spaces after commas
        value = value.replace(/,\s*/g, ', ');
        $(this).val(value);
    });
    
    // Real-time character count for description
    $('#location-description').on('input', function() {
        const length = $(this).val().length;
        let counter = $(this).next('.char-counter');
        
        if (counter.length === 0) {
            counter = $('<small class="char-counter" style="color: #666; float: right;"></small>');
            $(this).after(counter);
        }
        
        counter.text(length + '/500 characters');
        
        if (length > 500) {
            counter.css('color', 'red');
        } else {
            counter.css('color', '#666');
        }
    });
    
    // Coordinate validation and formatting
    $('#location-latitude, #location-longitude').on('blur', function() {
        const value = parseFloat($(this).val());
        const field = $(this).attr('id');
        
        if (!isNaN(value)) {
            if (field === 'location-latitude' && (value < -90 || value > 90)) {
                $(this).addClass('error');
                showNotification('Latitude must be between -90 and 90 degrees.', 'error');
            } else if (field === 'location-longitude' && (value < -180 || value > 180)) {
                $(this).addClass('error');
                showNotification('Longitude must be between -180 and 180 degrees.', 'error');
            } else {
                $(this).removeClass('error');
                $(this).val(value.toFixed(6)); // Format to 6 decimal places
            }
        }
    });
    
    // Enhanced search functionality for locations
    if ($('.lbp-locations-grid').length > 0) {
        const searchInput = $('<input type="text" placeholder="Search locations..." style="margin-bottom: 20px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">');
        $('.lbp-header').after(searchInput);
        
        searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.location-card').each(function() {
                const cardText = $(this).text().toLowerCase();
                if (cardText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
    
});

// Additional utility functions available globally
window.LBP_Admin = {
    showNotification: function(message, type) {
        const notification = jQuery('<div class="lbp-notification ' + type + '">' + message + '</div>');
        jQuery('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 5000);
    },
    
    refreshLocationsList: function() {
        location.reload();
    }
};