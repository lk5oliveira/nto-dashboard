/**
 * Universal Tracking Script
 * A reusable script for tracking various events and sending data to Google Sheets
 * 
 * Usage:
 * <script src="path/to/tracker.js"></script>
 * <script>
 *   // Initialize with default settings (auto-tracks page views)
 *   UniversalTracker.init();
 *   
 *   // Or initialize with custom settings
 *   UniversalTracker.init({
 *     endpoint: 'your-google-apps-script-url',
 *     autoTrackPageViews: true,
 *     cookieDays: 30,
 *     maxRetries: 3
 *   });
 *   
 *   // Track custom events
 *   UniversalTracker.track('ButtonClick', { button: 'signup', section: 'header' });
 *   UniversalTracker.track('FormSubmit', { form: 'contact', email: 'user@example.com' });
 * </script>
 */

(function (window) {
    'use strict';

    const UniversalTracker = {
        // Default configuration
        config: {
            endpoint: "https://script.google.com/macros/s/AKfycbxPUwqMd145QPGWsD6xuQ7RwkZVxWfENTLSy42X2JKA0ecjixFa1KW3kacTwaYYYybYdw/exec",
            autoTrackPageViews: true,
            cookieDays: 30,
            visitorCookieDays: 365,
            maxRetries: 3,
            retryDelay: 1000, // Base delay in ms
            debug: false
        },

        // Internal state
        visitorID: null,
        utmData: {},
        initialized: false,

        /**
         * Initialize the tracker
         * @param {Object} options - Configuration options
         */
        init: function (options = {}) {
            if (this.initialized) {
                this.log('Tracker already initialized');
                return;
            }

            // Merge user options with defaults
            this.config = { ...this.config, ...options };

            this.log('Initializing Universal Tracker with config:', this.config);

            // Initialize visitor tracking
            this.initializeVisitor();

            // Set initialized flag before auto-tracking
            this.initialized = true;

            // Auto-track page views if enabled
            if (this.config.autoTrackPageViews) {
                this.trackPageView();
            }

            this.log('Universal Tracker initialized successfully');
        },

        /**
         * Track a custom event
         * @param {string} eventType - Type of event (e.g., 'ButtonClick', 'FormSubmit')
         * @param {Object} eventData - Additional event data
         * @param {Object} options - Override options for this specific event
         */
        track: function (eventType, eventData = {}, options = {}) {
            if (!this.initialized) {
                console.error('UniversalTracker: Please call init() before tracking events');
                console.error('UniversalTracker: Event details:', { eventType, eventData });
                return;
            }

            const trackingData = this.prepareTrackingData(eventType, eventData);
            const endpoint = options.endpoint || this.config.endpoint;

            this.sendTracking(endpoint, trackingData, options.maxRetries || this.config.maxRetries);
        },

        /**
         * Track a page view (called automatically if autoTrackPageViews is true)
         */
        trackPageView: function () {
            this.track('PageView', {
                url: window.location.href,
                referrer: document.referrer || 'direct'
            });
        },

        /**
         * Initialize visitor tracking and UTM parameter collection
         */
        initializeVisitor: function () {
            // Set visitor ID
            this.visitorID = this.getCookie('visitorID');
            if (!this.visitorID) {
                this.visitorID = 'visitor-' + Math.random().toString(36).substring(2, 11);
                this.setCookie('visitorID', this.visitorID, this.config.visitorCookieDays);
            }

            // Collect and store UTM parameters
            const urlUTMParams = this.getUTMParamsFromURL();
            this.storeUTMCookies(urlUTMParams);
            this.utmData = this.getUTMCookies();

            this.log('Visitor initialized:', { visitorID: this.visitorID, utmData: this.utmData });
        },

        /**
         * Prepare tracking data with all necessary fields
         * @param {string} eventType - Type of event
         * @param {Object} eventData - Additional event data
         * @returns {Object} Complete tracking data
         */
        prepareTrackingData: function (eventType, eventData) {
            const currentDate = new Date();
            const formattedDate = currentDate.getFullYear() + '-' +
                String(currentDate.getMonth() + 1).padStart(2, '0') + '-' +
                String(currentDate.getDate()).padStart(2, '0') + ' ' +
                String(currentDate.getHours()).padStart(2, '0') + ':' +
                String(currentDate.getMinutes()).padStart(2, '0') + ':' +
                String(currentDate.getSeconds()).padStart(2, '0');

            // Base tracking data (value1-value12)
            const baseData = {
                value1: formattedDate, // Visit Time
                value2: this.visitorID, // Visitor ID (for Google Apps Script duplicate check)
                value3: eventType, // Event Type
                value4: eventData.url || window.location.href, // URL/Page
                value5: navigator.userAgent, // Browser Info
                value6: this.utmData.utm_medium || '', // UTM Medium
                value7: this.utmData.utm_campaign || '', // UTM Campaign
                value8: this.utmData.utm_source || '', // UTM Source
                value9: this.utmData.utm_content || '', // UTM Content
                value10: eventData.referrer || document.referrer || 'direct', // Referrer
                value11: document.title, // Page name/title
                value12: window.location.pathname // Page path
            };

            // Add custom fields (value13-value20) from eventData
            const customFields = {};
            let fieldIndex = 13;

            // Debug logging
            this.log('EventData received:', eventData);
            this.log('EventData entries:', Object.entries(eventData));

            // Extract custom fields from eventData (excluding standard ones)
            const excludeFields = ['url', 'referrer', 'title']; // Exclude fields already mapped to standard values
            for (const [key, value] of Object.entries(eventData)) {
                if (!excludeFields.includes(key) && fieldIndex <= 20 && value !== '' && value != null) {
                    customFields[`value${fieldIndex}`] = String(value);
                    this.log(`Adding custom field: value${fieldIndex} = ${value}`);
                    fieldIndex++;
                }
            }

            this.log('Custom fields added:', customFields);
            const finalData = { ...baseData, ...customFields };
            this.log('Final tracking data:', finalData);

            return finalData;
        },

        /**
         * Send tracking data with retry mechanism
         * @param {string} endpoint - Google Apps Script endpoint
         * @param {Object} data - Tracking data
         * @param {number} maxRetries - Maximum retry attempts
         */
        sendTracking: async function (endpoint, data, maxRetries) {
            const queryParams = new URLSearchParams(data);
            const url = `${endpoint}?${queryParams.toString()}`;

            // Always log the full URL being sent for debugging
            console.log('Tracker: Sending request to:', url);
            console.log('Tracker: Data being sent:', data);
            console.log('Tracker: Query params:', queryParams.toString());
            console.log('Tracker: URL length:', url.length);

            for (let attempt = 1; attempt <= maxRetries; attempt++) {
                try {
                    this.log(`Tracking attempt ${attempt}/${maxRetries} for event: ${data.value2}`);

                    await fetch(url, {
                        method: 'GET',
                        mode: 'no-cors'
                    });

                    this.log(`Event "${data.value2}" tracked successfully`);
                    return;

                } catch (error) {
                    console.error(`Tracking attempt ${attempt} failed:`, {
                        error: error.message,
                        eventType: data.value2,
                        url: url.substring(0, 100) + '...'
                    });

                    if (attempt === maxRetries) {
                        console.error(`All tracking attempts failed for event: ${data.value2}`, error);
                    } else {
                        // Exponential backoff
                        await new Promise(resolve =>
                            setTimeout(resolve, this.config.retryDelay * Math.pow(2, attempt - 1))
                        );
                    }
                }
            }
        },

        /**
         * Get UTM parameters from current URL
         * @returns {Object} UTM parameters
         */
        getUTMParamsFromURL: function () {
            const params = new URLSearchParams(window.location.search);
            return {
                utm_source: params.get('utm_source') || '',
                utm_medium: params.get('utm_medium') || '',
                utm_campaign: params.get('utm_campaign') || '',
                utm_content: params.get('utm_content') || '',
                utm_term: params.get('utm_term') || '',
                ref: params.get('ref') || '' // Include ref parameter
            };
        },

        /**
         * Store UTM parameters in cookies
         * @param {Object} utmParams - UTM parameters to store
         */
        storeUTMCookies: function (utmParams) {
            Object.keys(utmParams).forEach(key => {
                if (utmParams[key]) {
                    this.setCookie(key, utmParams[key], this.config.cookieDays);
                }
            });
        },

        /**
         * Retrieve UTM data from cookies
         * @returns {Object} UTM data from cookies
         */
        getUTMCookies: function () {
            return {
                utm_source: this.getCookie('utm_source') || '',
                utm_medium: this.getCookie('utm_medium') || '',
                utm_campaign: this.getCookie('utm_campaign') || '',
                utm_content: this.getCookie('utm_content') || '',
                utm_term: this.getCookie('utm_term') || '',
                ref: this.getCookie('ref') || ''
            };
        },

        /**
         * Set a cookie
         * @param {string} name - Cookie name
         * @param {string} value - Cookie value
         * @param {number} days - Days until expiration
         */
        setCookie: function (name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/`;
        },

        /**
         * Get a cookie value
         * @param {string} name - Cookie name
         * @returns {string|null} Cookie value or null
         */
        getCookie: function (name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },

        /**
         * Log debug messages if debug mode is enabled
         * @param {...any} args - Arguments to log
         */
        log: function (...args) {
            if (this.config.debug) {
                console.log('[UniversalTracker]', ...args);
            }
        },

        /**
         * Get current visitor ID
         * @returns {string} Visitor ID
         */
        getVisitorID: function () {
            return this.visitorID;
        },

        /**
         * Get current UTM data
         * @returns {Object} UTM data
         */
        getUTMData: function () {
            return { ...this.utmData };
        },

        /**
         * Update configuration after initialization
         * @param {Object} newConfig - New configuration options
         */
        updateConfig: function (newConfig) {
            this.config = { ...this.config, ...newConfig };
            this.log('Configuration updated:', this.config);
        }
    };

    // Make UniversalTracker available globally
    window.UniversalTracker = UniversalTracker;

    // Auto-initialize if data-auto-init attribute is present on script tag
    document.addEventListener('DOMContentLoaded', function () {
        const scriptTag = document.querySelector('script[src*="tracker.js"]');
        if (scriptTag && scriptTag.hasAttribute('data-auto-init')) {
            const autoConfig = {};

            // Read configuration from data attributes
            if (scriptTag.hasAttribute('data-endpoint')) {
                autoConfig.endpoint = scriptTag.getAttribute('data-endpoint');
            }
            if (scriptTag.hasAttribute('data-debug')) {
                autoConfig.debug = scriptTag.getAttribute('data-debug') === 'true';
            }

            UniversalTracker.init(autoConfig);
        }
    });

})(window);