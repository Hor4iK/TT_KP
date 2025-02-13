const validationConfig = {
  formSelector: '.popup__form',
  inputSelector: '.popup__input',
  submitButtonSelector: '.popup__button',
  inactiveButtonClass: 'button_disabled',
  inputErrorClass: 'popup__input_type_error',
  errorClass: 'popup__input-error_active'
};


// DOM-elements
const popupCall = document.querySelector('.popup_type_call');
const buttonCall = popupCall.querySelector('.popup__button');

const popupResult = document.querySelector('.popup_type_result');
const popupResultTitle = popupResult.querySelector('.popup__title');
const popupResultDescription = popupResult.querySelector('.popup_description');
const bannerForm = document.querySelector('.banner__form');

const buttonsFormCallArray = Array.from(document.querySelectorAll('.btn-form-call'));
const inputPhoneArray = Array.from(document.querySelectorAll('.popup__input_type_tel'));


//Function Close Modal Window
function closeModal() {
  const modal = document.querySelector('.popup_is-opened');
  const btnModalClose = modal.querySelector('.popup__close');

  if (modal) modal.classList.remove('popup_is-opened');

  document.removeEventListener('keydown', closeModalWithEsc);
  btnModalClose.removeEventListener('click', closeModal);
  modal.removeEventListener('mousedown', closeModalOverlay);
}

//Close Modal Window with button "Esc"
function closeModalWithEsc(evt) {
  if (evt.code === 'Escape') closeModal();
}

//Close Modal Window with Overlay
function closeModalOverlay(evt) {
  if (evt.target.classList.contains('popup')) closeModal();
}

//Function Open Modal Window
function openModal(modal) {
  const btnModalClose = modal.querySelector('.popup__close');

  modal.classList.add('popup_is-opened');

  btnModalClose.addEventListener('click', closeModal);
  document.addEventListener('keydown', closeModalWithEsc);
  modal.addEventListener('mousedown', closeModalOverlay);
}


//Add the error
const showInputError = (popupElement, inputElement, errorMessage, objConfig) => {
  const errorElement = popupElement.querySelector(`.${inputElement.id}_error`);
  inputElement.classList.add(objConfig.inputErrorClass);
  errorElement.textContent = errorMessage;
  errorElement.classList.add(objConfig.errorClass);
};

// Remove the error
const hideInputError = (popupElement, inputElement, objConfig) => {
  const errorElement = popupElement.querySelector(`.${inputElement.id}_error`);
  inputElement.classList.remove(objConfig.inputErrorClass);
  errorElement.classList.remove(objConfig.errorClass);
  errorElement.textContent = '';
};

// Finding invalid inputs
const hasInvalidInput = (inputList) => {
  return inputList.some(inputElement => {
    return !inputElement.validity.valid;
  })
}

//De/activating button
const changeStateSubmitButton = (btnElement, buttonClass, isDisabling) => {
  if (isDisabling) {
    btnElement.classList.add(buttonClass);
    btnElement.setAttribute("disabled", "");
  } else {
    btnElement.classList.remove(buttonClass);
    btnElement.removeAttribute("disabled", "");
  }
}

// Changing the button state
const toggleButtonState = (inputList, btnElement, objConfig) => {
  if (hasInvalidInput(inputList)) {
    changeStateSubmitButton(btnElement, objConfig.inactiveButtonClass, true);
  } else {
    changeStateSubmitButton(btnElement, objConfig.inactiveButtonClass, false);
  }
}

//Validation check
const checkInputValidity = (popupElement, inputElement, objConfig) => {
  if (inputElement.validity.patternMismatch) {
    inputElement.setCustomValidity(inputElement.dataset.errorMessage)
  } else {
    inputElement.setCustomValidity("");
  }
  if (inputElement.validity.valid) {
    hideInputError(popupElement, inputElement, objConfig);
  } else {
    showInputError(popupElement, inputElement, inputElement.validationMessage, objConfig);
  }
};

//Set event listeners at the popup`s inputs
const setEventListeners = (popupElement, objConfig) => {
  const inputList = Array.from(popupElement.querySelectorAll(objConfig.inputSelector));
  const btnElement = popupElement.querySelector(objConfig.submitButtonSelector);

  inputList.forEach(inputElement => {
    inputElement.addEventListener('input', function () {
      checkInputValidity(popupElement, inputElement, objConfig);
      toggleButtonState(inputList, btnElement, objConfig);
    });
  });
};

function enableValidation(objConfig) {
  const formList = Array.from(document.querySelectorAll(objConfig.formSelector));

  formList.forEach(popupElement => {
    popupElement.addEventListener('submit', (evt) => {
      evt.preventDefault();
    });
    setEventListeners(popupElement, objConfig);
  });
}

//Clear errors
function clearValidation(popupElement, objConfig) {
  const inputList = Array.from(popupElement.querySelectorAll(objConfig.inputSelector));
  const btnElement = popupElement.querySelector(objConfig.submitButtonSelector);

  changeStateSubmitButton(btnElement, objConfig.inactiveButtonClass, true);
  inputList.forEach(inputElement => {
    if (inputElement.classList.contains(objConfig.inputErrorClass)) {
      hideInputError(popupElement, inputElement, objConfig);
    }
  });
}



//Event handlers of Call form
buttonsFormCallArray.forEach(btn => {
  btn.addEventListener('click', () => {
    clearValidation(popupCall, validationConfig);
    openModal(popupCall);
  })
})

//Event handlers of input phone лун
inputPhoneArray.forEach(input => {
  input.addEventListener('keydown', evt => {
    if (evt.key != null && !RegExp('^[0-9]*$').test(evt.key) && evt.code != 'Backspace') {
      evt.preventDefault();
    }
  });
  input.addEventListener('click', evt => {
    input.setSelectionRange(4, 4);
  })
})

enableValidation(validationConfig);

$('.popup__input_type_tel').mask('+7 (999) 999-99-99');

$('.popup_type_call').submit(function (evt) {
  evt.preventDefault();

  const name = $(this).find('.popup__input_type_name').val().trim();
  const email = $(this).find('.popup__input_type_email').val().trim();
  const phone = $(this).find('.popup__input_type_tel').val().trim();
  const description = new Object();

  $.ajax({
    url: '/action/call.php',
    type: 'POST',
    dataType: 'json',
    data: {
      name: name,
      email: email,
      phone: phone
    },
    success: function (res) {
      popupResultTitle.textContent = 'Благодарим Вас за обращение!'
      popupResultDescription.textContent = res.message;
      openModal(popupResult);
    },
    error: function (res) {
      Object.assign(description, JSON.parse(res.responseText));
      popupResultTitle.textContent = 'Ошибка отправления заявки!';
      popupResultDescription.textContent = description.message;
      openModal(popupResult);
    }
  });
}
)


