/**
 * Frontend Logic & AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize frontend logic here
    console.log('Coffeeshop POS System Loaded');
});

// AJAX function untuk fetch data
function fetchData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}
