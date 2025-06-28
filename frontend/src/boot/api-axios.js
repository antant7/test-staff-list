import { boot } from 'quasar/wrappers';
import axios from 'axios';

import { notifyError, notifyWarning } from 'src/services/notify';
import { funcIsVarArray, funcIsVarObject, funcIsVarEmpty } from 'src/services/functions';

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)

// Включаем обработчики ошибок
// mfInitErrorReportHandler();

export default boot(() => {
  // По умолчанию отправлять куки - нужно для AVWaveform.js - делает отдельный запрос для анализа звукового файла
  // Куки падла AvWaveform.js при запросе mp3 файла не отправляет
  // !!! Внимание! Пришлось вручную добавить в исходники на 184 строку withCredentials: true,
  // в файле node_modules/vue-audio-visual/src/components/AvWaveform.js
  // !!! При следующем обновлении будет затерто !!!
  //     const conf = {
  //       responseType: 'arraybuffer',
  //       onDownloadProgress: this.downloadProgress,
  //       withCredentials: true,
  //     }
  // jetbrains://php-storm/navigate/reference?project=Mkc.Quasar&path=node_modules/vue-audio-visual/src/components/AvWaveform.js
  axios.defaults.withCredentials = true;
});

const Api = axios.create({
  baseURL: process.env.API_URL,

  // Почему-то OPTIONS предварительно не вызывается только при таком формате
  // без разбития на POST и другие типы запросов
  headers: {
    'Content-Type': 'text/plain',
  },
  // Обязательный параметр при авторизации на третьих сайтах типа api-mkc.bxb.delivery
  withCredentials: true,
});

/**
 * Получаем ошибки и варнинги из запроса - если нет - возвращаем пустой объект
 * Также показываем ошибки и варнинги если это не запрещено
 */
function apiGetErrors(response, opts = {}) {
  const options = {
    showErrors: true,
    showWarnings: true,
  };
  Object.assign(options, opts);

  const result = { errors: [], warnings: [] };
  ['errors', 'warnings'].forEach((key) => {
    if (funcIsVarArray(response.data[key]) && response.data[key].length > 0) {
      result[key] = response.data[key];

      // Capitalize
      const optName = `show${key.charAt(0).toUpperCase()}${key.substring(1).toLowerCase()}`;

      // Авто показ ошибок и варнингов
      if (options[optName]) {
        result[key].forEach((error) => {
          if (key === 'errors') {
            // Для страницы '/login' пропускаем ошибки вида "Доступ запрещен"
            if (window?.location?.pathname === '/login' && error?.code === 3) return;

            let message = error.message ?? '';
            // Если ошибка - Доступ запрещен - добавляем URL и сбрасываем progress
            if (error.code === 3) {
              message += ` -> ${response.config.url}`;
              notifyError({ message, progress: false, timeout: 0 });
            } else {
              notifyError({ message });
            }
          } else if (key === 'warnings') {
            notifyWarning({
              message: error.message,
            });
          }
        });
      }
    }
  });

  return result;
}

/**
 * Показываем ошибки
 */
function apiShowErrors(response) {
  const errors = apiGetErrors(response);
  if (funcIsVarArray(errors)) {
    errors.forEach((item) => {
      notifyError({ message: item.message });
    });
  }
}

/**
 * Успешный ли запрос к АПИ - Получили ли status = true в ответе
 * - заодно показываем пришедшие ошибки и варниги - если не запрещено настройками
 */
function apiIsSuccess(response, opts = {}) {
  if (!funcIsVarObject(response.data)) return false;

  const options = {
    showErrors: true,
    showWarnings: true,
  };
  Object.assign(options, opts);

  // Показываем ошибки и варнинги - если были в ответе
  apiGetErrors(response, options);

  return response.data.status === true;
}

/**
 * Получаем результат запроса - если результата нет - возвращаем default
 */
function apiGetResult(response, defaultValue = {}) {
  if (funcIsVarEmpty(response.data.data)) return defaultValue;

  // if (packageName) return !funcIsVarEmpty(response.data.data[packageName]) ? response.data.data[packageName] : defaultValue;

  // В случае если пустой packageName - возвращаем весь результат
  return response.data.data;
}

// Вешаем автообработчик всех ответов - сначала обработка здесь - потом проброс дальше
Api.interceptors.request.use();

// Вешаем автообработчик всех ответов - сначала обработка здесь - потом проброс дальше
Api.interceptors.response.use(
  // При необходимости сюда можно включить автообработчик ответов вида
  // (response) => {
  // Что-то делаем с ответом и пробрасываем результат дальше
  // return response
  // },
  (response) => response,
  // Автообработчик всех ответов сервера вне диапазона 2хх -
  // вместо повторения в каждом вызове Axios .error((e) => console.error(e))

  (error) => {
    if (error.response) {
      // Проверяем authTill - если -1 - сразу разлогин
      // console.log('+++++++ error.response = ', error.response);
        // Если в ответе есть ошибки - показываем
        apiShowErrors(error.response);
    }
    // Вызов console.error произойдет автоматом дальше -
    // .catch((e) => console.error(e)) при каждом вызове можно не писать
    // console.error(error);

    // Any status codes that falls outside the range of 2xx cause this function to trigger
    // Do something with response error
    return Promise.reject(error);
  },
);

export {
  Api, apiIsSuccess, apiGetResult, apiGetErrors,
};
