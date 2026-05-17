const registrationSystem = document.getElementById("registration-system");

// 2. Get Form Steps & Navigation Buttons
const steps = document.querySelectorAll(".form-step");
const nextBtns = document.querySelectorAll(".next-btn");
const prevBtns = document.querySelectorAll(".prev-btn");

let currentStep = 0;
let pendingRegistrationPromise = null;

// Initialize registration system
if (registrationSystem) {
    updateStepper(); // Call this to set the very first state!
    updateNextButtonState(); // Initialize button state
}

// Helper Function: Update the Stepper Pill States
function updateStepper() {
    const stepContainers = document.querySelectorAll(".stepper-pill .step");
    stepContainers.forEach((step, index) => {
        const circle = step.querySelector(".step-circle");
        
        // Remove old classes first
        circle.classList.remove("active", "completed", "inactive");
        if (index < currentStep) {
            circle.classList.add("completed"); // This is a PAST page -> Add Checkmark
        } 
        else if (index === currentStep) {
            circle.classList.add("active"); // This is the CURRENT page -> Show Waving Dots
        } 
        else {
            circle.classList.add("inactive");  // This is a FUTURE page -> Show Static Dots
        }
    });
}

// check if all forms are filled up before proceeding to next page
function isStepValid(stepIndex) {
    const step = steps[stepIndex];
    const inputs = step.querySelectorAll("input, select");
    const checkedRadioGroups = new Set();
    
    for (let input of inputs) {
        const inputType = input.type;

        if (input.id === 'contact-number' || input.id === 'emergency-contact') {
            input.classList.remove('invalid-phone');
        }

        // Check all inputs
        if (["text", "email", "tel", "date", "file"].includes(inputType)) {
            if (input.hasAttribute("required") && !input.value.trim()) return false;

            // check if the inputted phone number has 11 digits
            if ((input.id === 'contact-number' || input.id === 'emergency-contact') && input.value.trim()) {
                const digits = input.value.replace(/\D/g, '');
                if (digits.length !== 11) {
                    input.classList.add('invalid-phone');
                    return false;
                }
            }
        }
        // Check select dropdowns
        else if (input.tagName === "SELECT") {
            if (input.hasAttribute("required") && !input.value) return false;
        }
        // Check radio buttons
        else if (inputType === "radio") {
            const radioName = input.name;
            if (!checkedRadioGroups.has(radioName)) {
                const radioGroup = step.querySelectorAll(`input[name="${radioName}"]`);
                const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                if (!isChecked) return false;
                checkedRadioGroups.add(radioName);
            }
        }
        // Check checkboxes
        else if (inputType === "checkbox") {
            if (!input.checked) return false;
        }
    }
    
    return true;
}

// enable next button
function updateNextButtonState() {
    const nextBtn = steps[currentStep].querySelector(".next-btn");
    if (nextBtn) {
        if (isStepValid(currentStep)) {
            nextBtn.disabled = false;
            nextBtn.style.opacity = "1";
            nextBtn.style.cursor = "pointer";
        } else {
            nextBtn.disabled = true;
            nextBtn.style.opacity = "0.5";
            nextBtn.style.cursor = "not-allowed";
        }
    }
}

// Add event listeners to all form inputs for real-time validation
const formInputs = document.querySelectorAll("#market-form input, #market-form select");
formInputs.forEach((input) => {
    input.addEventListener("change", updateNextButtonState);
    input.addEventListener("input", updateNextButtonState);
});

// Date of Birth Validation
const dobInput = document.getElementById('dob-input');
if (dobInput) {
    dobInput.addEventListener('blur', validateDateOfBirth);
}

function validateDateOfBirth() {
    const dobValue = dobInput.value;
    
    // Remove any previous warning
    removeAgeWarning();
    
    // Only validate if the date is complete (format: YYYY-MM-DD)
    if (!dobValue || dobValue.length !== 10) return;
    
    // Check if year is valid (4 digits, not all zeros)
    const yearPart = dobValue.substring(0, 4);
    if (!/^\d{4}$/.test(yearPart) || yearPart === '0000') return;
    
    const dob = new Date(dobValue);
    const today = new Date();
    const currentYear = today.getFullYear();
    
    // Calculate age
    let age = currentYear - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    const dobYear = dob.getFullYear();
    
    // Check if age is less than 18
    if (age < 18) {
        showAgeWarning('You must be 18 or older to register.');
    }
    // Check if age is 100 or more, or year is in the future
    else if (age >= 100 || dobYear > currentYear) {
        showAgeWarning('Invalid year inputted.');
    }
}

function showAgeWarning(message) {
    // Remove existing warning if any
    removeAgeWarning();
    
    const warningBox = document.createElement('div');
    warningBox.className = 'age-warning-popup';
    warningBox.textContent = message;
    
    document.body.appendChild(warningBox);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        warningBox.remove();
    }, 5000);
}

function removeAgeWarning() {
    const existingWarning = document.querySelector('.age-warning-popup');
    if (existingWarning) {
        existingWarning.remove();
    }
}

function goToNextStep() {
    if (currentStep < steps.length - 1) {
        steps[currentStep].classList.remove("step-active");
        currentStep++;
        steps[currentStep].classList.add("step-active");
        updateStepper();
        updateNextButtonState();
    }
}

function buildStep1FormData() {
    const formData = new FormData();
    // Build fullname from split fields for backward-compatibility
    const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
    const middleInitial = document.querySelector('input[name="middle_initial"]')?.value.trim() || '';
    const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
    const fullnameCombined = [firstName, middleInitial, lastName].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
    formData.append('fullname', fullnameCombined);
    formData.append('first_name', firstName);
    formData.append('middle_initial', middleInitial);
    formData.append('last_name', lastName);
    formData.append('sex', document.querySelector('select[name="sex"]').value);
    formData.append('date_of_birth', document.querySelector('input[name="date_of_birth"]').value);
    formData.append('contact_number', document.querySelector('input[name="contact_number"]').value.trim());
    formData.append('emergency_contact', document.querySelector('input[name="emergency_contact"]').value.trim());
    formData.append('address', document.querySelector('input[name="address"]').value.trim());
    formData.append('business_name', document.querySelector('input[name="business_name"]').value.trim());
    formData.append('category', document.querySelector('select[name="category"]').value);
    return formData;
}

function submitStep1Registration() {
    if (pendingRegistrationPromise) {
        return pendingRegistrationPromise;
    }

    const request = (async () => {
        const response = await fetch('api/submit.php?action=register', {
            method: 'POST',
            body: buildStep1FormData()
        });

        const responseText = await response.text();
        console.log('Response status:', response.status);
        console.log('Response text:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (error) {
            console.error('Failed to parse JSON:', error);
            throw new Error('Server error: Check browser console and XAMPP error logs.');
        }

        if (!data.success) {
            const parts = [];
            if (data.error) parts.push(data.error);
            if (data.detail) parts.push(data.detail);
            if (data.errors && Array.isArray(data.errors)) parts.push(data.errors.join(', '));
            throw new Error(parts.join(' | ') || 'Unknown error');
        }

        localStorage.setItem('registration_id', data.registration_id);
        return data.registration_id;
    })();

    pendingRegistrationPromise = request;

    request.finally(() => {
        if (pendingRegistrationPromise === request) {
            pendingRegistrationPromise = null;
        }
    });

    return request;
}

function getRegistrationIdOrWait() {
    const registrationId = localStorage.getItem('registration_id');
    if (registrationId) {
        return Promise.resolve(registrationId);
    }

    if (pendingRegistrationPromise) {
        return pendingRegistrationPromise.then((id) => String(id));
    }

    return Promise.reject(new Error('Missing registration ID. Please go back to Step 1 and submit again.'));
}

// Next Button Function
nextBtns.forEach((btn) => {
    btn.addEventListener("click", async () => {
        if (!isStepValid(currentStep) || currentStep >= steps.length - 1) return;

        // Special handling for Step 1: Submit data via AJAX
        if (currentStep === 0) {
            const registrationSavePromise = submitStep1Registration();
            goToNextStep();

            registrationSavePromise.catch((error) => {
                console.error('Registration save error:', error);
                alert(error.message || 'Failed to save registration details.');
            });
        } else if (currentStep === 1) {
            let registrationId;

            try {
                registrationId = await getRegistrationIdOrWait();
            } catch (error) {
                alert(error.message);
                return;
            }

            const formData = new FormData();
            formData.append('registration_id', registrationId);
            formData.append('q1', document.querySelector('input[name="q1"]:checked')?.value || '');
            formData.append('q2', document.querySelector('input[name="q2"]:checked')?.value || '');
            formData.append('q3', document.querySelector('input[name="q3"]:checked')?.value || '');
            formData.append('q4', document.querySelector('input[name="q4"]:checked')?.value || '');
            formData.append('q5', document.querySelector('input[name="q5"]:checked')?.value || '');
            formData.append('q6', document.querySelector('input[name="q6"]:checked')?.value || '');

            const agreementNames = [
                'agree_health_certificates',
                'agree_clean_stall',
                'agree_proper_waste_disposal',
                'agree_noncompliance_termination',
                'agree_products_fresh_legal',
                'agree_fit_to_work'
            ];

            agreementNames.forEach((name) => {
                const isChecked = document.querySelector(`input[name="${name}"]`)?.checked;
                formData.append(name, isChecked ? '1' : '0');
            });

            try {
                const response = await fetch('api/submit.php?action=health_declaration', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse health declaration JSON:', e, responseText);
                    alert('Server error while saving health declaration. Check console/XAMPP logs.');
                    return;
                }

                if (data.success) {
                    goToNextStep();
                } else {
                    const parts = [];
                    if (data.error) parts.push(data.error);
                    if (data.detail) parts.push(data.detail);
                    if (data.errors && Array.isArray(data.errors)) parts.push(data.errors.join(', '));
                    alert('Error: ' + (parts.join(' | ') || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error: ' + error.message);
                console.error('Health declaration fetch error:', error);
            }
        } else if (currentStep === 2) {
            // Step 3: submit files via FormData to server
            let registrationId;

            try {
                registrationId = await getRegistrationIdOrWait();
            } catch (error) {
                alert(error.message);
                return;
            }

            const fileFields = [
                'barangay_clearance',
                'dti_registration',
                'government_id',
                'health_certificate',
                'sanitary_permit',
                'vaccine_card'
            ];

            // Friendly labels for file fields shown to users
            const fileFieldLabels = {
                'barangay_clearance': 'Barangay Clearance',
                'dti_registration': 'DTI Business Registration',
                'government_id': 'Valid Government ID',
                'health_certificate': 'Health Certificate',
                'sanitary_permit': 'Sanitary Permit',
                'vaccine_card': 'COVID Vaccine Card'
            };

            const fd = new FormData();
            fd.append('registration_id', registrationId);

            for (const name of fileFields) {
                const input = document.querySelector(`input[name="${name}"]`);
                if (!input || !input.files || !input.files[0]) {
                    const label = fileFieldLabels[name] || name.replace(/_/g, ' ');
                    alert('Please attach file for: ' + label);
                    return;
                }
                fd.append(name, input.files[0]);
            }

            try {
                const resp = await fetch('api/submit.php?action=requirements', {
                    method: 'POST',
                    body: fd
                });

                const text = await resp.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse requirements response:', e, text);
                    alert('Server error while uploading files. Check console/XAMPP logs.');
                    return;
                }

                if (data.success) {
                    goToNextStep();
                } else {
                    const parts = [];
                    if (data.error) parts.push(data.error);
                    if (data.detail) parts.push(data.detail);
                    if (data.errors && Array.isArray(data.errors)) parts.push(data.errors.join(', '));
                    alert('Error: ' + (parts.join(' | ') || 'Unknown error'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
                console.error('Upload error:', err);
            }
        } else {
            // Default behavior for other steps
            goToNextStep();
        }
    });
});

// Back Button Function
prevBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
        if (currentStep > 0) {
            steps[currentStep].classList.remove("step-active");
            currentStep--;
            steps[currentStep].classList.add("step-active");
            updateStepper(); // Update dots
        }
    });
});