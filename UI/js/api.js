// ============================================================
// js/api.js
// Central API communication layer.
// All fetch calls to the PHP backend go through here.
// ============================================================

// Use relative path - works regardless of project location or XAMPP root
const BASE_URL = '../dairy_farm_backend/api';

const API = {

  // ── Generic request ──────────────────────────────────────
  async request(endpoint, method = 'GET', body = null) {
    const headers = { 'Content-Type': 'application/json' };

    // Attach CSRF token for all state-changing requests
    if (['POST', 'PUT', 'DELETE'].includes(method)) {
      const token = localStorage.getItem('csrf_token');
      if (token) headers['X-CSRF-Token'] = token;
    }

    const options = {
      method,
      headers,
      credentials: 'include', // always send the session cookie
    };

    if (body !== null) options.body = JSON.stringify(body);

    try {
      const res  = await fetch(`${BASE_URL}/${endpoint}`, options);
      const json = await res.json();

      if (!json.success) throw new Error(json.message || 'Request failed');

      return json.data ?? json;
    } catch (err) {
      console.error(`[API Error] ${method} ${endpoint}:`, err.message);
      throw err;
    }
  },

  // ── Auth ──────────────────────────────────────────────────
  auth: {
    /**
     * Register a new account.
     * @param {{ username: string, email: string, password: string, role?: string }} data
     */
    signup(data) {
      return API.request('signup.php', 'POST', data);
    },

    /**
     * Log in and receive a CSRF token + user object.
     * @param {{ username: string, password: string }} data
     */
    login(data) {
      return API.request('auth.php?action=login', 'POST', data);
    },

    /** Destroy the server session. */
    logout() {
      return API.request('auth.php?action=logout', 'POST');
    },

    /** Check whether the session is still alive. */
    status() {
      return API.request('auth.php?action=status');
    },
  },

  // ── Customers ────────────────────────────────────────────
  customers: {
    getAll:   ()        => API.request('customers.php'),
    getById:  (id)      => API.request(`customers.php?id=${id}`),
    create:   (data)    => API.request('customers.php', 'POST', data),
    update:   (id, d)   => API.request(`customers.php?id=${id}`, 'PUT', d),
    delete:   (id)      => API.request(`customers.php?id=${id}`, 'DELETE'),
  },

  // ── Cows ──────────────────────────────────────────────────
  cows: {
    getAll:   ()        => API.request('cows.php'),
    getById:  (id)      => API.request(`cows.php?id=${id}`),
    create:   (data)    => API.request('cows.php', 'POST', data),
    update:   (id, d)   => API.request(`cows.php?id=${id}`, 'PUT', d),
    delete:   (id)      => API.request(`cows.php?id=${id}`, 'DELETE'),
  },

  // ── Workers ───────────────────────────────────────────────
  workers: {
    getAll:   ()        => API.request('workers.php'),
    getById:  (id)      => API.request(`workers.php?id=${id}`),
    create:   (data)    => API.request('workers.php', 'POST', data),
    update:   (id, d)   => API.request(`workers.php?id=${id}`, 'PUT', d),
    delete:   (id)      => API.request(`workers.php?id=${id}`, 'DELETE'),
  },

  // ── Orders ────────────────────────────────────────────────
  orders: {
    getAll:        ()       => API.request('orders.php'),
    getById:       (id)     => API.request(`orders.php?id=${id}`),
    getByCustomer: (cid)    => API.request(`orders.php?customer=${cid}`),
    create:        (data)   => API.request('orders.php', 'POST', data),
    update:        (id, d)  => API.request(`orders.php?id=${id}`, 'PUT', d),
    delete:        (id)     => API.request(`orders.php?id=${id}`, 'DELETE'),
  },
};