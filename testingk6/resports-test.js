import http from 'k6/http';
import { check, sleep } from 'k6';
import { FormData } from 'https://jslib.k6.io/formdata/0.0.2/index.js';

export let options = {
    vus: 50,
    duration: '2m',
};

export default function () {
    const BASE_URL = 'http://localhost:8000/api';

    // LOGIN
    const form = new FormData();
    form.append('email', 'citra@mail.com');
    form.append('password', 'password');

    const loginRes = http.post(`${BASE_URL}/login`, form.body(), {
        headers: {
            'Content-Type': 'multipart/form-data; boundary=' + form.boundary,
        },
    });

    check(loginRes, {
        'Login berhasil': (res) => res.status === 200 && res.json('token') !== '',
    });

    const token = loginRes.json('token');
    if (!token) {
        console.error('Gagal login, token tidak tersedia');
        return;
    }

    // GET SCHEDULE
    const scheduleRes = http.get(`${BASE_URL}/reservations/1/schedules`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    check(scheduleRes, {
        'Jadwal berhasil diambil': (res) => res.status === 200,
    });

    // POST RESERVATION
    const today = new Date().toISOString().split('T')[0];
    const getRandomTimeIds = () => {
        const allTimeIds = [1, 2, 3, 4, 5, 6];
        const randomCount = Math.floor(Math.random() * 2) + 1; // pilih 1–2 jam
        return allTimeIds.sort(() => 0.5 - Math.random()).slice(0, randomCount);
    };


    const reservationPayload = {
        userId: 9,
        name: 'Citra',
        total: 150000,
        details: [
            {
                fieldId: 5,
                timeIds: getRandomTimeIds(), // ambil 1-2 jam secara acak
                date: today,
            },
        ],
    };

    const reservationRes = http.post(`${BASE_URL}/reservations`, JSON.stringify(reservationPayload), {
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    check(reservationRes, {
        'Reservasi berhasil atau konflik (expected)': (res) =>
            res.status === 201 || res.status === 409,
    });

    if (reservationRes.status === 409) {
        console.warn('Reservasi gagal karena konflik jadwal:', reservationRes.json('conflicts'));
    }

    console.log('Status reservasi:', reservationRes.status);
    console.log('Body reservasi:', reservationRes.body);

    sleep(1);
}
