/**
 * Сервис для показа нотификаций
 */
import { Notify, type QNotifyUpdateOptions } from 'quasar';

// Все сообщения убирать автоматом - Юля Цацурина Скайп 13.02.2023 15:40
const timeoutDefault = 5000;

/**
 * Успешное уведомление
 * @param options
 */
function notifySuccess(options: QNotifyUpdateOptions = {}) {
  const opts = {
    ...{
      message: 'Пустое сообщение',
      color: 'green',
      textColor: 'white',
      icon: 'check_circle',
      timeout: timeoutDefault,
      progress: false,
      actions: [
        { label: 'OK', color: 'white', handler: () => { /* ... */ } },
      ],
    },
    ...options,
  };

  return notifyCreate(opts);
}

/**
 * Показываем уведомление
 * @param options
 */
function notifyInfo(options: QNotifyUpdateOptions = {}) {
  const opts = {
    ...{
      message: 'Пустое сообщение',
      color: 'primary',
      textColor: 'white',
      icon: 'mdi-information-outline',
      timeout: timeoutDefault,
      progress: false,
      actions: [
        { label: 'OK', color: 'white', handler: () => { /* ... */ } },
      ],
    },
    ...options,
  };

  return notifyCreate(opts);
}

/**
 * Показываем ошибку - возможно стоит сделать очистку от XSS
 * Как вариант https://github.com/cure53/DOMPurify --------- https://www.npmjs.com/package/dompurify
 * @param options
 */
function notifyError(options: QNotifyUpdateOptions = {}) {
  const opts = {
    ...{
      group: false,
      message: 'Пустое сообщение',
      color: 'red-5',
      textColor: 'white',
      icon: 'warning',
      timeout: timeoutDefault,
      progress: false,
      actions: [
        { label: 'OK', color: 'white', handler: () => { /* ... */ } },
      ],
    },
    ...options,
  };

  return notifyCreate(opts);
}

/**
 * Показываем варнинги
 * @param options
 */
function notifyWarning(options: QNotifyUpdateOptions = {}) {
  const opts = {
    ...{
      message: 'Пустое сообщение',
      color: 'warning',
      textColor: 'white',
      icon: 'mdi-alert-circle-outline',
      timeout: timeoutDefault,
      progress: false,
      actions: [
        { label: 'OK', color: 'white', handler: () => { /* ... */ } },
      ],
    },
    ...options,
  };

  return notifyCreate(opts);
}

/**
 * Общий для всех
 */
function notifyCreate(opts: QNotifyUpdateOptions = {}) {
  // Если включен таймер - автоматически включаем линию-индикатор
  if (typeof opts?.timeout !== 'undefined' && opts.timeout > 0) opts.progress = true;

  return Notify.create(opts);
}

export {
  notifySuccess, notifyInfo, notifyError, notifyWarning,
};
