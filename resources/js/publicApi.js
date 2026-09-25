import axios from 'axios';

const publicApi = axios.create({
  baseURL: '/api/public',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
  withCredentials: true,
});

export default publicApi;