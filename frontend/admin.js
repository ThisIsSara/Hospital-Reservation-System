// Administrator interface for the Sprint 2 management stories.
let token = '';
const page = document.body.dataset.page;
const message = document.getElementById('message');

let noticeTimer;
function clearNotices() {
    clearTimeout(noticeTimer);
    message.hidden = true;
    document.querySelectorAll('.form-result').forEach(notice => notice.remove());
}
function dismissLater(notice) {
    noticeTimer = setTimeout(() => {
        if (notice === message) message.hidden = true;
        else notice.remove();
    }, 4000);
}
function notify(text, success = false) {
    clearNotices();
    message.hidden = false;
    message.className = 'alert ' + (success ? 'success' : 'error');
    message.textContent = text;
    message.scrollIntoView({ block: 'nearest' });
    dismissLater(message);
}
function notifyForm(form, text, success = false) {
    clearNotices();
    const notice = document.createElement('div');
    notice.className = 'form-result alert ' + (success ? 'success' : 'error');
    notice.setAttribute('role', 'status');
    notice.textContent = text;
    form.insertAdjacentElement('afterend', notice);
    dismissLater(notice);
}
async function request(file, fields = null) {
    const options = { credentials: 'same-origin' };
    if (fields) {
        fields.set('csrf_token', token);
        options.method = 'POST';
        options.body = fields;
    }
    const response = await fetch('../backend/' + file, options);
    const data = await response.json();
    if (!response.ok || !data.success) {
        const error = new Error(data.message || 'Request failed.');
        error.status = response.status;
        throw error;
    }
    return data;
}
async function act(action, values) {
    const fields = new FormData();
    fields.set('action', action);
    for (const [key, value] of Object.entries(values)) fields.set(key, String(value));
    try {
        const result = await request('admin_api.php', fields);
        await load();
        notify(result.message, true);
    } catch (error) {
        notify(error.message);
    }
}
function element(tag, value = '') {
    const node = document.createElement(tag);
    node.textContent = value;
    return node;
}
function actionButton(label, handler) {
    const button = element('button', label);
    button.type = 'button';
    button.className = 'small';
    button.addEventListener('click', handler);
    return button;
}
function addRow(parent, title, subtitle, controls = []) {
    const row = element('div');
    row.className = 'admin-row';
    const detail = element('div');
    detail.append(element('strong', title), element('p', subtitle));
    const actions = element('div');
    actions.className = 'admin-actions';
    controls.forEach(control => actions.append(control));
    row.append(detail, actions);
    parent.append(row);
}
function setOptions(select, items, label, getValue, getText) {
    select.replaceChildren(new Option(label, ''));
    for (const item of items) select.add(new Option(getText(item), String(getValue(item))));
}
function promptUpdate(current, label) {
    const answer = window.prompt(label, current);
    return answer === null ? null : answer.trim();
}
async function load() {
    const data = await request('admin_api.php');
    const s = data.statistics;
    document.getElementById('statistics').textContent =
        'Patients: ' + s.patients + '   |   Doctors: ' + s.doctors +
        '   |   Reservations: ' + s.appointments + '   |   Available times: ' + s.available_slots;
    setOptions(document.getElementById('doctor-specialty'), data.specialties, 'Choose specialty', x => x, x => x);
    setOptions(document.getElementById('slot-doctor'), data.doctors, 'Choose doctor', x => x.id, x => x.name);

    const specialties = document.getElementById('specialty-list');
    specialties.replaceChildren();
    for (const specialty of data.specialties) {
        addRow(specialties, specialty, '', [
            actionButton('Rename', () => {
                const name = promptUpdate(specialty, 'New specialty name:');
                if (name && name !== specialty) act('rename_specialty', { old_name: specialty, name });
            }),
            actionButton('Delete', () => {
                if (confirm('Delete specialty ' + specialty + '?')) act('delete_specialty', { old_name: specialty });
            })
        ]);
    }
    const doctors = document.getElementById('doctor-list');
    doctors.replaceChildren();
    for (const doctor of data.doctors) {
        addRow(doctors, doctor.name, doctor.specialty, [
            actionButton('Edit', () => {
                const name = promptUpdate(doctor.name, 'Doctor name:');
                if (!name) return;
                const specialty = promptUpdate(doctor.specialty, 'Specialty (use a name from the Specialties list):');
                if (specialty) act('update_doctor', { id: doctor.id, name, specialty });
            }),
            actionButton('Delete', () => {
                if (confirm('Delete doctor ' + doctor.name + '?')) act('delete_doctor', { id: doctor.id });
            })
        ]);
    }
    const slots = document.getElementById('slot-list');
    slots.replaceChildren();
    for (const slot of data.slots) {
        const taken = slot.appointment_id !== null;
        addRow(slots, slot.doctor_name, slot.appointment_date + ' · ' + slot.appointment_time.slice(0, 5) +
            (taken ? ' — Booked' : ' — Available'), [
            actionButton('Delete Time', () => {
                if (confirm('Remove this appointment time?')) act('delete_slot', { id: slot.id });
            })
        ]);
    }
    const reservations = document.getElementById('reservation-list');
    reservations.replaceChildren();
    for (const reservation of data.appointments) {
        addRow(reservations, 'Reservation #' + reservation.id + ' — ' + reservation.patient_name,
            reservation.doctor_name + ' · ' + reservation.appointment_date + ' · ' + reservation.appointment_time.slice(0, 5), [
            actionButton('Reschedule', () => {
                const options = data.slots.filter(slot => slot.appointment_id === null &&
                    new Date(slot.appointment_date + 'T' + slot.appointment_time).getTime() > Date.now());
                if (!options.length) { notify('No available appointment times.'); return; }
                const choices = options.map(slot => slot.id + ': ' + slot.doctor_name + ' — ' +
                    slot.appointment_date + ' ' + slot.appointment_time.slice(0, 5)).join('\n');
                const chosen = window.prompt('Choose a new slot ID from the list:\n' + choices);
                if (chosen === null) return;
                const id = Number(chosen.trim());
                if (!Number.isInteger(id) || !options.some(slot => slot.id === id)) {
                    notify('Enter one of the available slot IDs.');
                    return;
                }
                act('reschedule_appointment', { id: reservation.id, slot_id: id });
            }),
            actionButton('Cancel', () => {
                if (confirm('Cancel reservation #' + reservation.id + '?'))
                    act('cancel_appointment', { id: reservation.id });
            })
        ]);
    }
    const patients = document.getElementById('patient-list');
    patients.replaceChildren();
    for (const patient of data.patients) {
        addRow(patients, patient.full_name, patient.email, [
            actionButton('Edit', () => {
                const name = promptUpdate(patient.full_name, 'Patient name:');
                if (!name) return;
                const email = promptUpdate(patient.email, 'Patient email:');
                if (email) act('update_patient', { id: patient.id, name, email });
            }),
            actionButton('Delete', () => {
                if (confirm('Delete patient ' + patient.full_name + ' and their reservations?'))
                    act('delete_patient', { id: patient.id });
            })
        ]);
    }
}
async function start() {
    try {
        const session = await request('session.php');
        token = session.csrf_token;
        if (page === 'admin-login') {
            document.getElementById('admin-login-form').addEventListener('submit', async event => {
                event.preventDefault();
                const button = event.currentTarget.querySelector('button');
                button.disabled = true;
                try {
                    const result = await request('admin_login.php', new FormData(event.currentTarget));
                    window.location.href = result.redirect;
                } catch (error) {
                    notify(error.message);
                    button.disabled = false;
                }
            });
            return;
        }
        document.getElementById('admin-logout').addEventListener('click', async () => {
            try {
                await request('logout.php', new FormData());
                window.location.href = 'admin-login.html';
            } catch (error) { notify(error.message); }
        });
        for (const [formId, action] of [
            ['specialty-form', 'add_specialty'],
            ['doctor-form', 'add_doctor'],
            ['slot-form', 'add_slot'],
            ['patient-form', 'add_patient']
        ]) {
            document.getElementById(formId).addEventListener('submit', async event => {
                event.preventDefault();
                const form = event.currentTarget;
                const button = form.querySelector('button');
                button.disabled = true;
                const fields = new FormData(form);
                fields.set('action', action);
                try {
                    const result = await request('admin_api.php', fields);
                    form.reset();
                    notifyForm(form, result.message, true);
                    await load();
                } catch (error) {
                    notifyForm(form, error.message);
                } finally {
                    button.disabled = false;
                }
            });
        }
        await load();
    } catch (error) {
        if (error.status === 401 && page === 'admin') window.location.href = 'admin-login.html';
        else notify(error.message);
    }
}
start();
