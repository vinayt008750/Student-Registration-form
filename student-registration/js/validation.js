/**
 * Student Registration System - Frontend Validation & Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Setup Student Form Validation (Registration & Edit)
    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        initStudentFormValidation(studentForm);
    }

    // 2. Setup Image Upload Preview
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or WEBP).');
                    this.value = '';
                    return;
                }

                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB limit. Please choose a smaller image.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    photoPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%; height:100%; object-fit:cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Setup Live Table Search & Filter for Admin Dashboard
    const searchInput = document.getElementById('adminSearchInput');
    const courseFilter = document.getElementById('adminCourseFilter');
    const tableBody = document.getElementById('studentsTableBody');

    if (searchInput && tableBody) {
        function filterTable() {
            const query = searchInput.value.toLowerCase().trim();
            const selectedCourse = courseFilter ? courseFilter.value.toLowerCase().trim() : '';
            const rows = tableBody.getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                const courseCell = row.querySelector('.col-course') ? row.querySelector('.col-course').textContent.toLowerCase() : '';

                const matchesQuery = !query || text.includes(query);
                const matchesCourse = !selectedCourse || courseCell.includes(selectedCourse);

                if (matchesQuery && matchesCourse) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', filterTable);
        if (courseFilter) {
            courseFilter.addEventListener('change', filterTable);
        }
    }

    // 4. Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }
    });
});

/**
 * Validates student form fields
 */
function initStudentFormValidation(form) {
    const fields = {
        full_name: {
            el: form.querySelector('#full_name'),
            validate: val => val.length >= 3 && /^[a-zA-Z\s\.\'-]+$/.test(val),
            message: 'Full Name must be at least 3 characters (letters & spaces only).'
        },
        dob: {
            el: form.querySelector('#dob'),
            validate: val => {
                if (!val) return false;
                const dobDate = new Date(val);
                const today = new Date();
                const age = (today - dobDate) / (365.25 * 24 * 60 * 60 * 1000);
                return dobDate < today && age >= 14 && age <= 100;
            },
            message: 'Please enter a valid Date of Birth (Age must be at least 14).'
        },
        email: {
            el: form.querySelector('#email'),
            validate: val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
            message: 'Please provide a valid email address (e.g. user@example.com).'
        },
        phone: {
            el: form.querySelector('#phone'),
            validate: val => /^[+]?[0-9]{10,15}$/.test(val.replace(/[\s-]/g, '')),
            message: 'Enter a valid 10-15 digit phone number.'
        },
        address: {
            el: form.querySelector('#address'),
            validate: val => val.trim().length >= 5,
            message: 'Residential address must be at least 5 characters long.'
        },
        course: {
            el: form.querySelector('#course'),
            validate: val => val.trim() !== '',
            message: 'Please select a course.'
        },
        semester: {
            el: form.querySelector('#semester'),
            validate: val => val.trim() !== '',
            message: 'Please select a semester.'
        }
    };

    // Attach real-time input listeners
    Object.keys(fields).forEach(key => {
        const item = fields[key];
        if (item.el) {
            item.el.addEventListener('input', () => validateField(item));
            item.el.addEventListener('blur', () => validateField(item));
        }
    });

    // Handle form submit
    form.addEventListener('submit', (e) => {
        let isFormValid = true;

        Object.keys(fields).forEach(key => {
            const item = fields[key];
            if (item.el) {
                const valid = validateField(item);
                if (!valid) {
                    isFormValid = false;
                }
            }
        });

        if (!isFormValid) {
            e.preventDefault();
            // Scroll to first invalid field
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
}

function validateField(item) {
    const el = item.el;
    const value = el.value.trim();
    const isValid = item.validate(value);

    let feedbackEl = el.parentElement.querySelector('.invalid-feedback');
    if (!feedbackEl) {
        feedbackEl = document.createElement('div');
        feedbackEl.className = 'invalid-feedback';
        el.parentElement.appendChild(feedbackEl);
    }
    feedbackEl.textContent = item.message;

    if (!isValid) {
        el.classList.add('is-invalid');
        el.classList.remove('is-valid');
        return false;
    } else {
        el.classList.remove('is-invalid');
        el.classList.add('is-valid');
        return true;
    }
}

/**
 * Confirm deletion modal/prompt
 */
function confirmDelete(studentName) {
    return confirm(`Are you sure you want to permanently delete the student record for "${studentName}"? This action cannot be undone.`);
}
