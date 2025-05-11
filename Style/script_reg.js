document.getElementById('add-form').addEventListener('submit', function(event) {
    var form = this; // Сохраняем ссылку на форму
    var validation = validateForm(form); // Вызываем функцию валидации, передавая форму в качестве аргумента
    if (validation) {
      // Если валидация прошла успешно, можно отправить данные на сервер
      var formData = new FormData(form); // Создаем объект FormData на основе формы
      // Далее можно использовать объект formData для отправки данных на сервер, например, с помощью fetch или XMLHttpRequest
      // Пример использования fetch:
      fetch('/submit', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        // Обрабатываем ответ от сервера
      })
      .catch(error => {
        // Обрабатываем ошибку
      });
    } else {
      event.preventDefault(); // Если валидация не прошла, предотвращаем отправку формы
    }
  });
  
  function validateForm(form) {
    function removeError(input) {
      const parent = input.parentNode;
      if (parent.classList.contains('error')) {
        parent.querySelector('.error-label').remove();
        parent.classList.remove('error');
      }
    }
  
    function createError(input, text) {
      const parent = input.parentNode;
      const errorLabel = document.createElement('label');
      errorLabel.classList.add('error-label');
      errorLabel.textContent = text;
      parent.classList.add('error');
      parent.append(errorLabel);
    }
  
    function isValidEmail(email) {
      // Регулярное выражение для проверки email
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailPattern.test(email);
    }
  
    let result = true;
  
    const allInputs = form.querySelectorAll('input');
  
    let password = null;
  
    for (const input of allInputs) {
      removeError(input);
      if (input.value === '') {
        createError(input, 'Поле не заполнено');
        result = false;
      } else if (input.type === 'email' && !isValidEmail(input.value)) {
        createError(input, 'Введите корректный email');
        result = false;
      } else if (input.type === 'password') {
        if (password === null) {
          password = input.value;
        } else if (password !== input.value) {
          createError(input, 'Пароли не совпадают');
          result = false;
        }
      }
    }
    return result;
  }
  
  