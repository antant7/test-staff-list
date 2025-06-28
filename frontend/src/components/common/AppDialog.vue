<template>
  <q-dialog
    v-model="dialogOpen"
    @show="showDialog()"
    @hide="closeDialog()"
    :persistent="props.persistent"
    :full-width="props.fullWidth"
    :full-height="props.fullHeight"
  >
    <q-card :id="`dialog-${uniqId}`" :style="computedCardStyles">
      <!-- Показываем слот Title - если был определен в слоте -->
      <div v-if="slots.title && slots.title().length > 0" :id="`dialog-title-${uniqId}`">
        <slot name="title"/>
      </div>
      <!-- Title по умолчанию -->
      <q-card-section v-else :id="`dialog-title-${uniqId}`" class="q-pa-none">
        <q-toolbar>
          <q-toolbar-title :class="props.titleClass">
            {{ props.titleText }}
          </q-toolbar-title>
          <q-btn
            @click="closeDialog()"
            icon="close"
            color="grey"
            padding="sm"
            flat
          />
        </q-toolbar>
      </q-card-section>

      <q-separator/>

      <q-card-section
        :class="!props.noOverflow ? 'scroll' : ''"
        :style="`max-height: ${scrollableHeight}px`"
      >
        <slot/>
      </q-card-section>

      <q-inner-loading :showing="props.loading">
        <q-spinner-gears size="50px" color="primary"/>
      </q-inner-loading>
    </q-card>

  </q-dialog>

</template>

<script setup lang="ts">
/**
 *  Собственный компонент диалога с фиксированной шапкой и автоматическим расчетом максимально допустимой высоты окна
 *  Внимание!!! Использует window.onResize - теоретически возможны проблемы с производительностью
 */
import {
  ref, watch, computed, useSlots,
} from 'vue';

const slots = useSlots();

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  titleText: { type: String, default: '' },
  titleClass: { type: String, default: '' },
  width: { type: String, default: null },
  maxWidth: { type: String, default: null },
  height: { type: String, default: null },
  maxHeight: { type: String, default: null },
  loading: { type: Boolean, default: false },
  persistent: { type: Boolean, default: false },
  fullWidth: { type: Boolean, default: false },
  fullHeight: { type: Boolean, default: false },
  rules: { type: Array, default: null },
  noOverflow: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'onUpdate:modelValue', 'hide', 'show']);

const clientHeightRef = ref();

// Function to generate unique short ID
const funcUniqId = (): string => {
  return Math.random().toString(36).substring(2, 8);
};

const uniqId = ref(funcUniqId());
const scrollableHeight = ref();

// Модель показа диалога - да/нет
const dialogOpen = ref(props.modelValue);

// Отслеживание внешнего изменения статуса показа диалога
watch(() => props.modelValue, (newVal) => {
  dialogOpen.value = newVal;
});

// Вычисляем стили для отображения окна диалога - ширина/высота
const computedCardStyles = computed(() => {
  const arr = !props.noOverflow ? ['overflow: hidden'] : [];
  if (props.width) arr.push(`width: ${props.width}`);
  if (props.maxWidth) arr.push(`max-width: ${props.maxWidth}`);
  if (props.height) arr.push(`height: ${props.height}`);
  if (props.maxHeight) arr.push(`max-height: ${props.maxHeight}`);

  return arr.join(';');
});

// Обработчик на изменение размеров области просмотра для перерасчета внутренней части окна диалога
// чтобы вешать/удалять при открытии/закрытии окна
const onResizeFunc = () => {
  // Получаем доступную клиенту высоту области просмотра
  const { clientHeight } = document.documentElement;
  // console.log('+++++ window.onresize AAAAAA', clientHeight);

  let cardHeight = 0;
  const cardElem = document.getElementById(`dialog-${uniqId.value}`);
  if (cardElem) cardHeight = cardElem.offsetHeight;

  let titleHeight = 0;
  const titleElem = document.getElementById(`dialog-title-${uniqId.value}`);
  if (titleElem) titleHeight = titleElem.offsetHeight;

  scrollableHeight.value = Math.round(clientHeight * 0.9 - titleHeight);
  const check = scrollableHeight.value + titleHeight;
  if (check > cardHeight && check > clientHeight * 0.9) scrollableHeight.value = cardHeight - titleHeight;

  clientHeightRef.value = clientHeight;
};

// Срабатывает при открытии диалога
function showDialog() {
  onResizeFunc();
  window.addEventListener('resize', onResizeFunc);
  emit('show');
}

// Метод закрытия окна по кнопке Закрыть или клику ВНЕ пределов q-dialog
// onUpdate:modelValue - в случае если есть блок emits:[] - иначе получаем Vue Warn
function closeDialog() {
  emit('onUpdate:modelValue', false); // НЕ помню почему так - пока оставлю
  emit('update:modelValue', false);
  window.removeEventListener('resize', onResizeFunc);
  emit('hide');
}

</script>

<style></style>
