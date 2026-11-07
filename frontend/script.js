// Connect the forms to PHP. The server validates all submitted data.
const page = document.body.dataset.page;
let csrfToken = '';

function showMessage(text, success = false) {
    const message = document.getElementById('message');
    message.hidden = false;
    message.className = 'alert ' + (success ? 'success' : 'error');
    message.textContent = text;
}

async function api(file, formData = null) {
    const options = { credentials: 'same-origin' };
    if (formData) {
        formData.set('csrf_token', csrfToken);
        options.method = 'POST';
        options.body = formData;
    }
    const response = await fetch('../backend/' + file, options);
    const data = await response.json();
    if (!response.ok || !data.success) {
        const error = new Error(data.message || 'The request failed. Please try again.');
        error.status = response.status;
        throw error;
    }
    return data;
}

function textElement(tag, text, className = '') {
    const element = document.createElement(tag);
    element.textContent = text;
    element.className = className;
    return element;
}

async function loadDoctors() {
    const data = await api('doctors.php');
    const filter = document.getElementById('specialty-filter');
    const selected = filter.value;
    const specialties = [...new Set(data.doctors.map(doctor => doctor.specialty))].sort();
    filter.replaceChildren(new Option('All specialties', ''));
    for (const specialty of specialties) filter.add(new Option(specialty, specialty));
    filter.value = specialties.includes(selected) ? selected : '';
    const visibleDoctors = data.doctors.filter(doctor => !filter.value || doctor.specialty === filter.value);
    const list = document.getElementById('doctors-list');
    list.replaceChildren();
    if (visibleDoctors.length === 0) {
        list.append(textElement('p', 'No doctors found for this specialty.'));
    }
    if (data.doctors.length === 0) {
        list.append(textElement('p', 'No doctors have been added yet.'));
    }
    for (const doctor of visibleDoctors) {
        const card = document.createElement('article');
        card.className = 'doctor-card';
        card.append(textElement('p', doctor.specialty, 'eyebrow'));
        card.append(textElement('h2', doctor.name));
        const slots = data.slots.filter(slot => String(slot.doctor_id) === String(doctor.id));
        if (slots.length === 0) {
            card.append(textElement('p', 'No available appointment times.'));
        }
        const times = document.createElement('ul');
        times.className = 'slot-list';
        for (const slot of slots) {
            const row = document.createElement('li');
            row.append(textElement('span', slot.appointment_date + ' · ' + slot.appointment_time.slice(0, 5)));
            const link = textElement('a', 'Book Appointment', 'button small');
            link.href = 'booking.html?slot_id=' + encodeURIComponent(slot.id);
            row.append(link);
            times.append(row);
        }
        card.append(times);
        list.append(card);
    }
}

async function loadBooking() {
    const slotId = new URLSearchParams(window.location.search).get('slot_id');
    if (!slotId || !/^[1-9][0-9]*$/.test(slotId)) {
        throw new Error('Choose an appointment from the doctors page.');
    }
    const data = await api('doctors.php?slot_id=' + encodeURIComponent(slotId));
    document.getElementById('doctor-name').textContent = data.slot.name;
    document.getElementById('doctor-specialty').textContent = data.slot.specialty;
    document.getElementById('appointment-date').textContent = data.slot.appointment_date;
    document.getElementById('appointment-time').textContent = data.slot.appointment_time.slice(0, 5) + ' (Riyadh)';
    document.getElementById('slot_id').value = data.slot.id;
    document.querySelector('#booking-form button').disabled = false;
}

function connectForm(id, endpoint) {
    const form = document.getElementById(id);
    if (!form) return;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        let keepDisabled = false;
        try {
            const data = await api(endpoint, new FormData(form));
            const message = data.appointment_id
                ? data.message + ' Reservation #' + data.appointment_id + '.' : data.message;
            showMessage(message, true);
            if (id === 'login-form') window.location.href = data.redirect;
            if (id === 'registration-form') form.reset();
            if (id === 'booking-form') keepDisabled = true;
        } catch (error) {
            showMessage(error.message);
            if (error.status === 401 && id === 'booking-form') window.location.href = 'login.html';
        } finally {
            button.disabled = keepDisabled;
        }
    });
}

const registrationForm = document.getElementById('registration-form');
if (registrationForm) {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('confirm_password');
    function checkPasswords() {
        confirmation.setCustomValidity(password.value === confirmation.value ? '' : 'Passwords do not match.');
    }
    password.addEventListener('input', checkPasswords);
    confirmation.addEventListener('input', checkPasswords);
}
connectForm('registration-form', 'register.php');
connectForm('login-form', 'login.php');
connectForm('booking-form', 'book.php');

async function initialize() {
    try {
        const session = await api('session.php');
        csrfToken = session.csrf_token;
        if ((page === 'doctors' || page === 'booking') && !session.logged_in) {
            window.location.href = 'login.html';
            return;
        }
        const logout = document.getElementById('logout-button');
        if (logout) {
            logout.addEventListener('click', async () => {
                try {
                    await api('logout.php', new FormData());
                    window.location.href = 'login.html';
                } catch (error) { showMessage(error.message); }
            });
        }
        if (page === 'register' || page === 'login') document.querySelector('button[type="submit"]').disabled = false;
        if (page === 'doctors') {
            document.getElementById('patient-welcome').textContent = 'Welcome, ' + session.patient_name;
            await loadDoctors();
            document.getElementById('specialty-filter').addEventListener('change', loadDoctors);
        }
        if (page === 'booking') await loadBooking();
    } catch (error) {
        showMessage(error.message);
        if (error.status === 401 && (page === 'doctors' || page === 'booking')) window.location.href = 'login.html';
    }
}
initialize();
