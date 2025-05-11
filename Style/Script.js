const form = document.getElementById('registration-form');
const registerButton = document.getElementById('register-button');

form.addEventListener('input', e => {
	const username = document.getElementById('username');
	const email = document.getElementById('email');
	const password = document.getElementById('password');
	const confirmPassword = document.getElementById('confirm-password');
	const country = document.getElementById('country');

	if (username.value.length >= 6 &&
		email.checkValidity() &&
		password.value.length >= 8 &&
		password.value === confirmPassword.value &&
		country.value !== '') {
		registerButton.classList.add('active');
	} else {
		registerButton.classList.remove('active');
	}
});