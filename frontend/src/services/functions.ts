/**
 * Модуль глобальных функций
 */

import { date, type DateUnitOptions } from 'quasar';
// import { DateUnitOptions } from 'quasar/dist/types/utils/date';

/** ------------------------------ Методы Определения типов переменных -----------------------------------
 * ПАМЯТКА! Object возвращается не только на "чистые" объекты данных, но и на конструкторы типа Error
 */
function funcGetVarType(value: unknown): string {
  // Исходная версия = /^\[object (\S+?)\]$/ - ESlint говорит слеш в \] не нужен
  const regex = /^\[object (\S+?)]$/;
  const matches = Object.prototype.toString.call(value).match(regex) || [];

  return (matches[1] || 'undefined').toLowerCase();
}

function funcIsVarObject(value: unknown) { return funcGetVarType(value) === 'object'; }
function funcIsVarArray(value: unknown) { return funcGetVarType(value) === 'array'; }
function funcIsVarUndefined(value: unknown) { return funcGetVarType(value) === 'undefined'; }
function funcIsVarString(value: unknown) {
  // console.log('+++++++++ funcGetVarType(value)', funcGetVarType(value));
  return funcGetVarType(value) === 'string';
}
function funcIsVarBoolean(value: unknown) {
  return funcGetVarType(value) === 'boolean';
}
// Проверяем на null, undefined и пустой массив/объект
function funcIsVarEmpty(value: unknown) {
  return funcGetVarType(value) === 'null' || funcIsVarUndefined(value)
    || (Array.isArray(value) && value.length === 0)
    || (typeof value === 'string' && value.length === 0)
    || (funcIsVarObject(value) && JSON.stringify(value) === JSON.stringify({}));
}

// Безопасно получаем число
function fnSafeNum(value: number|undefined, fallback = 0) {
  const num = Number(value);
  return Number.isNaN(num) ? fallback : num;
}

/** -------------------- Завершение Методов определения типов переменных ------------------------------- */
export interface ISecToStrParams {
  showDays?: boolean
  showHours?: boolean
  showMinutes?: boolean
  showSeconds?: boolean
}
/**
 * Переводим секунды в указанный формат 00:00:00 (по умолчанию мин:сек) - минуты показываем всегда
 */
function funcSecondsToString(seconds = 0, params: ISecToStrParams = {}): string {
  const defaultParams: ISecToStrParams = {
    showDays: false, showHours: false, showMinutes: true, showSeconds: true,
  };
  const p: ISecToStrParams = { ...defaultParams, ...params };

  // Приводим к + значению
  seconds = Math.abs(seconds);

  let sec = Math.abs(Math.floor(seconds) % 60).toString();
  let min = (seconds >= 60 ? Math.abs(Math.floor(seconds / 60) % 60) : 0).toString();

  if (sec.toString().length === 1) sec = `0${sec}`;
  if (min.toString().length === 1) min = `0${min}`;

  let result = p.showSeconds ? `${min}:${sec}` : `${min}`;

  // Если времени более часа - автоматически показываем часы
  if (p.showHours || seconds >= 3600) {
    // Math.floor округляет числа между 0 и 1 - до 1 - вместо понижения до 0
    let hours = (seconds >= 3600 ? Math.abs(Math.floor(seconds / 60 / 60) % 24) : 0).toString();
    if (hours.toString().length === 1) hours = `0${hours}`;
    result = `${hours}:${result}`;
  }

  // Если показывать дни - добавляем если есть
  if (p.showDays) {
    const daysFloat = seconds !== 0 ? Math.abs(seconds / 60 / 60 / 24) : 0;
    const days = daysFloat > 0 ? Math.floor(daysFloat) : 0;
    if (days !== 0) result = `${days} дн ${result}`;
  }

  return result;
}

/** Переводим секунды в слова 1 дн 2 ч 3 мин 4 сек */
function funcSecondsToWord(seconds = 0, params: ISecToStrParams = {}): string {
  const p: ISecToStrParams = {
    showDays: false, showHours: true, showMinutes: true, showSeconds: false, ...params,
  };

  // Приводим к + значению
  seconds = Math.abs(seconds);

  const sec = `${Math.abs(Math.floor(seconds) % 60).toString()} сек`;
  const min = `${(seconds >= 60 ? Math.abs(Math.floor(seconds / 60) % 60) : 0).toString()} мин`;

  let result = p.showSeconds ? `${min} ${sec}` : `${min}`;

  // Если времени более часа - автоматически показываем часы
  if (p.showHours || seconds >= 3600) {
    // Math.floor округляет числа между 0 и 1 - до 1 - вместо понижения до 0
    const hours = (seconds >= 3600 ? Math.abs(Math.floor(seconds / 60 / 60) % 24) : 0).toString();
    result = `${hours} ч ${result}`;
  }

  // Если показывать дни - добавляем если есть
  if (p.showDays) {
    const daysFloat = seconds !== 0 ? Math.abs(seconds / 60 / 60 / 24) : 0;
    const days = daysFloat > 0 ? Math.floor(daysFloat) : 0;
    if (days !== 0) result = `${days} дн ${result}`;
  }

  return result;
}

/**
 * Вычисляем Дней / Часов Минут назад от указанной даты
 */
function funcTimeHowLongAgo(datetime: string): string {
  const dateFrom = new Date(datetime);
  const curDate = new Date();
  const unit = 'minutes';

  const minutes = date.getDateDiff(curDate, dateFrom, unit);

  let result = '';

  // Если более суток назад - показываем дни и часы
  if (minutes > 1440) {
    const hours = Math.abs(Math.floor(minutes / 60) % 24);
    const days = Math.floor(minutes / 1440);

    result = `${days} дн ${hours} ч`;
  } else if (minutes > 60) { // Если более часа назад - часы и минуты
    const min = Math.abs(Math.floor(minutes) % 60);
    const hours = Math.floor(minutes / 60);

    result = `${hours} ч ${min} мин`;
  } else {
    const min = Math.abs(Math.floor(minutes) % 60);

    result = `${min} мин`;
  }

  return result;
}

/**
 * Функция для форматирования даты / времени в любом месте кода
 * Показывает время по часовому поясу установленному на текущем ПК
 * Входящая дата в формате совместимом с RFC 2822 & DATE_ATOM
 */
function funcFormatDateTimeFromRFC(dateString: string, format = 'DD.MM.YYYY HH:mm:ss') {
  if (funcIsVarEmpty(dateString)) return '';
  if (!date.isValid(dateString)) return `Ошибка даты: ${dateString}`;

  return date.formatDate(dateString, format);
}

/**
 * Форматирования даты в RFC 2822 или DATE_ATOM
 * @param myDate JavaScript native Date object myDate = new Date();
 */
function funcFormatDateTimeToAtom(myDate?: Date) {
  if (funcIsVarEmpty(myDate)) myDate = new Date();

  return date.formatDate(myDate, 'YYYY-MM-DDTHH:mm:ssZZ');
}

/**
 * Расчитываем разницу в датах
 * @param dateStrFrom Дата от которой надо рассчитать - как правило, более ранняя
 * @param dateStrTo Дата до которой надо рассчитать - как правило, более поздняя (текущее время по умолчанию)
 * @param unit Единицы измерения https://quasar.dev/quasar-utils/date-utils#difference
 * @returns {number}
 */
function funcCalcDiffBetweenDates(dateStrFrom: string, dateStrTo?: string, unit: DateUnitOptions|undefined = 'seconds'): number {
  if (!date.isValid(dateStrFrom)) return 0;
  // if (!date.isValid(dateStrFrom)) return `Ошибка dateStrFrom: ${dateStrFrom}`;

  if (!dateStrTo || funcIsVarEmpty(dateStrTo)) dateStrTo = new Date().toUTCString();

  if (!dateStrTo || !date.isValid(dateStrTo)) return 0;
  // if (!date.isValid(dateStrTo)) return `Ошибка dateStrTo: ${dateStrTo}`;

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  return date.getDateDiff(dateStrFrom, dateStrTo, unit);
}

/**
 * Расчет попадания локального времени клиента в диапазон с 9:00 до 19:00
 *
 * @param clientLocalTime !!! Локальное время клиента - Обязательно в формате HH:mm
 * @returns {boolean}
 */
function funcIsWorkingTime(clientLocalTime: string): boolean {
  // Локальное время клиента
  const strTime = clientLocalTime?.split(':');
  if (!funcIsVarArray(strTime) || strTime[0] === undefined) return false;

  const dateTarget = new Date();
  dateTarget.setHours(Number(strTime[0]), Number(strTime[1]));

  // Рабочее время от - сегодня с 9 утра
  const dateFrom = new Date();
  dateFrom.setHours(9, 0, 0, 0); // '09:00:00'

  // Рабочее время до - до 19 вечера
  const dateTo = new Date();
  dateTo.setHours(19, 0, 0, 0); // '19:00:00'

  return date.isBetweenDates(dateTarget, dateFrom, dateTo);
}

/** Выдаем текстовые значения первого и крайнего дня текущей недели */
function fnCalcFirstLastDateInWeek() {
  const curDayOfWeek = date.getDayOfWeek(new Date());
  const firstDayInWeek = new Date().getDate() - curDayOfWeek + 1;

  return [
    date.formatDate(new Date().setDate(firstDayInWeek), 'YYYY-MM-DD'),
    date.formatDate(new Date().setDate(firstDayInWeek + 6), 'YYYY-MM-DD'),
  ];
}

/**
 * Генерируем уникальный ID - в первую очередь для v-for чтобы не заморачиваться с :key=""
 */
function funcUniqId(prefix = '', random = false) {
  const sec = Date.now() * 1000 + Math.random() * 1000;
  const id = sec.toString(16).replace(/\./g, '').padEnd(14, '0');
  return `${prefix}${id}${random ? `.${Math.trunc(Math.random() * 100000000)}` : ''}`;
}

/**
 * funcMillisToSeconds - переводим милисекунды в секунды (Время реакции)
 */
function funcMillisToSeconds(millis: number, fractionDigits = 0) {
  // toFixed(3) вернет 0.300; toFixed(1) вернет 0.3
  return (millis / 1000).toFixed(fractionDigits);
}

/**
 * Валидация Email
 */
function funcIsEmailValid(email: string) {
  // eslint-disable-next-line no-useless-escape
  const pattern = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
  return pattern.test(email);
}

/**
 * Правим телефон на 7 в начале
 */
function funcPhoneTo7(phone = '') {
  if (funcIsVarEmpty(phone)) return '';

  phone = phone.toString().replace(/[^.\d]+/g, '');
  if (phone.length === 11 && phone.startsWith('89')) phone = `7${phone.slice(1)}`;

  return phone;
}

/**
 * Простое глубокое клонирование - медленный метод
 */
function funcJsonClone(objectToClone: object) {
  return JSON.parse(JSON.stringify(objectToClone));
}

/**
 * Простое глубокое клонирование - медленный метод
 */
function funcStrReplace(str: string, find: string[], replace: string[]) {
  for (let i = 0; i < find.length; i += 1) {
    const findItem = find[i];
    const replaceItem = replace[i];
    if (findItem !== undefined && replaceItem !== undefined) {
      str = str.replace(findItem, replaceItem);
    }
  }
  return str;
}

/**
 * Вырезаем HTML теги - аналог strip_tags на PHP
 */
function funcStripTags(str = '') {
  return str.replace(/<\/?[^>]+>/gi, '');
}


/**
 * Функция форматирования чисел в денежном формате - аналог php Number_format()
 */
function funcMoneyFormat(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUR' }).format(amount);
}

/**
 * Функция форматирования чисел аналог php Number_format() - по умолчанию число в виде 1,025.3452
 */
function funcNumberFormat(amount: number, locale = 'en-EN') {
  amount = fnSafeNum(amount);

  return new Intl.NumberFormat(locale, {
    maximumFractionDigits: 2,
  }).format(amount);
}

function fnFindDuplicatesArrayOfArrays(arrayOfArrays: unknown[]): unknown[] {
  const seenArrays = new Set();
  const duplicateArrays = [];

  for (const arr of arrayOfArrays) {
    // Преобразуем каждый подмассив в строку для сравнения
    const arrString = JSON.stringify(arr);

    if (seenArrays.has(arrString)) {
      duplicateArrays.push(arr);
    } else {
      seenArrays.add(arrString);
    }
  }

  return duplicateArrays;
}

/**
 * Иногда требуется эмулировать действие и ничего не делать
 */
function funcDoNothing() {}

export {
  // Функции проверки переменных
  funcIsVarEmpty, funcIsVarObject, funcIsVarArray, funcIsVarString, funcIsVarUndefined, funcIsVarBoolean, fnSafeNum,
  // Функции даты
  funcFormatDateTimeFromRFC, funcFormatDateTimeToAtom, funcCalcDiffBetweenDates, funcIsWorkingTime, fnCalcFirstLastDateInWeek,
  // Функции времени
  funcSecondsToString, funcSecondsToWord, funcMillisToSeconds, funcTimeHowLongAgo,
  // Функции валидации
  funcIsEmailValid, // funcSetToInt,
  // Другие функции
  funcUniqId, funcJsonClone, funcStripTags, funcDoNothing, funcPhoneTo7, funcMoneyFormat, funcNumberFormat,
  funcStrReplace,

  // Функции массивов
  fnFindDuplicatesArrayOfArrays,
};
