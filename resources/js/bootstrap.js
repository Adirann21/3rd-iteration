// Standard HTTP client used by the frontend for AJAX requests.
import axios from 'axios';
window.axios = axios;

// Ensure Laravel recognizes the requests as AJAX and can handle CSRF/headers correctly.
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
