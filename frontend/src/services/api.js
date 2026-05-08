import axios from 'axios';

const axiosClient = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

const existingToken = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

if (existingToken) {
    axiosClient.defaults.headers.common.Authorization = `Bearer ${existingToken}`;
}

export const setAuthToken = (token) => {
    localStorage.setItem('auth_token', token);
    axiosClient.defaults.headers.common.Authorization = `Bearer ${token}`;
};

export const clearAuthToken = () => {
    localStorage.removeItem('auth_token');
    delete axiosClient.defaults.headers.common.Authorization;
};

export default axiosClient;