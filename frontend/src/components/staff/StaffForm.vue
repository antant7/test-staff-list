<template>
  <AppDialog
    v-model="dialog"
    @hide="closeDialog"
    width="90%"
    max-width="1000px"
  >
    <template #title>
      <div class="q-pa-sm flex justify-between items-center">
        <div class="text-h6">
          {{ user?.id ? 'Редактировать пользователя' : 'Добавить пользователя' }}
        </div>
        <div>
          <q-btn @click="saveUser()" :loading="saving" icon="save" label="Сохранить"
                 color="green" padding="sm" no-caps outline/>
          <q-btn @click="closeDialog()" :loading="saving" icon="close" color="red" class="q-ml-md" padding="sm"
                 no-caps outline/>
        </div>
      </div>
    </template>

    <q-form @submit="onSubmit" @reset="onReset" class="row q-col-gutter-sm">
        <!-- Parent Staff Search field -->
        <div class="col-4">
          <q-select
            v-model="selectedParent"
            :options="staffOptions"
            option-label="displayName"
            option-value="id"
            label="Родительский сотрудник"
            outlined
            clearable
            :loading="loadingStaff"
            @update:model-value="onParentSelect"
          >
            <template v-slot:before-options>
              <q-input
                v-model="searchQuery"
                placeholder="Введите фамилию для поиска..."
                outlined
                dense
                class="q-ma-sm"
                @update:model-value="onSearchInput"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
            </template>

            <template v-slot:no-option>
              <q-input
                v-model="searchQuery"
                placeholder="Поиск по фамилии"
                outlined
                dense
                class="q-ma-sm"
                @update:model-value="onSearchInput"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>

              <q-separator />

              <q-item>
                <q-item-section class="text-grey">
                  {{ searchQuery ? 'Сотрудники не найдены' : 'Начните набирать фамилию' }}
                </q-item-section>
              </q-item>
            </template>

            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section>
                  <q-item-label>{{ scope.opt.lastName }} {{ scope.opt.firstName }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.position }} (ID: {{ scope.opt.id }})</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- First Name field -->
        <div class="col-4">
          <q-input
            v-model="formData.firstName"
            label="Имя"
            outlined
            :rules="[val => !!val || 'Поле обязательно для заполнения']"
          />
        </div>

        <!-- Last Name field -->
        <div class="col-4">
          <q-input
            v-model="formData.lastName"
            label="Фамилия"
            outlined
            :rules="[val => !!val || 'Поле обязательно для заполнения']"
          />
        </div>

        <!-- Position field -->
        <div class="col-4">
          <q-input
            v-model="formData.position"
            label="Должность"
            outlined
            :rules="[val => !!val || 'Поле обязательно для заполнения']"
          />
        </div>

        <!-- Email field -->
        <div class="col-4">
          <q-input
            v-model="formData.email"
            type="email"
            label="Email"
            outlined
            :rules="[
            val => !!val || 'Поле обязательно для заполнения',
            val => /.+@.+\..+/.test(val) || 'Введите корректный email'
          ]"
          />
        </div>

        <!-- Home Phone field -->
        <div class="col-4">
          <q-input
            v-model="formData.homePhone"
            label="Домашний телефон"
            outlined
            mask="+# (###) ###-##-##"
            :rules="[val => !!val || 'Поле обязательно для заполнения']"
          />
        </div>

        <!-- Notes field -->
        <div class="col-12">
          <q-input
            v-model="formData.notes"
            type="textarea"
            label="Заметки"
            outlined
            rows="3"
          />
        </div>

    </q-form>
   </AppDialog>
</template>

<script setup lang="ts">
/* ------------------------------------ Импорт ------------------------------------ */
import {ref, watch} from 'vue'
import {useQuasar} from 'quasar'

import type {IStaff} from 'src/types/staff'
import {Api} from 'boot/api-axios'
import type {AxiosError} from 'axios'

import AppDialog from '../common/AppDialog.vue'

/* --------------------------------- Props и Emits --------------------------------- */
const props = defineProps({
  modelValue: {type: Boolean, default: false},
  user: {type: Object as () => IStaff, default: () => ({})}
});

const emit = defineEmits(['update:model-value', 'update:user', 'user-saved'])

/* ----------------------------- Объявление переменных ---------------------------- */
const $q = useQuasar()
const dialog = ref(false)
const saving = ref(false)
const loadingStaff = ref(false)
const staffOptions = ref<IStaff[]>([])
const selectedParent = ref<IStaff | null>(null)
const searchQuery = ref('')

const formData = ref<Omit<IStaff, 'id'>>({
  pid: props.user?.pid || 0,
  firstName: props.user?.firstName || '',
  lastName: props.user?.lastName || '',
  position: props.user?.position || '',
  email: props.user?.email || '',
  homePhone: props.user?.homePhone || '',
  notes: props.user?.notes || ''
})

/* ------------------------------- Функции (методы) ------------------------------- */
const saveUser = async () => {
  try {
    saving.value = true

    // Prepare data for API request
    const requestData = {
      pid: formData.value.pid,
      firstName: formData.value.firstName,
      lastName: formData.value.lastName,
      position: formData.value.position,
      email: formData.value.email,
      homePhone: formData.value.homePhone,
      notes: formData.value.notes
    }

    let response

    if (props.user?.id) {
      // Update existing user with PUT request
      response = await Api.put(`/api/staff/${props.user.id}`, requestData)
    } else {
      // Create new user with POST request
      response = await Api.post('/api/staff', requestData)
    }

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: response.data.message || (props.user?.id ? 'Пользователь успешно обновлен' : 'Пользователь успешно создан'),
        position: 'top'
      })

      // Emit success event with updated data
      emit('user-saved', response.data.data)
      emit('update:user', response.data.data)

      // Close dialog
      closeDialog()
    } else {
      $q.notify({
        type: 'negative',
        message: response.data.message || 'Ошибка при сохранении пользователя',
        position: 'top'
      })
    }
  } catch (error: unknown) {
    console.error('Error saving user:', error)

    let errorMessage = 'Ошибка при сохранении пользователя'

    // Type guard to check if error is AxiosError
    if (error && typeof error === 'object' && 'response' in error) {
      const axiosError = error as AxiosError<{message?: string; errors?: Record<string, string[]>}>
      if (axiosError.response?.data?.message) {
        errorMessage = axiosError.response.data.message
      } else if (axiosError.response?.data?.errors) {
        // Handle validation errors
        const errors = Object.values(axiosError.response.data.errors).flat()
        errorMessage = errors.join(', ')
      }
    }

    $q.notify({
      type: 'negative',
      message: errorMessage,
      position: 'top'
    })
  } finally {
    saving.value = false
  }
}

const closeDialog = () => {
  dialog.value = false
  emit('update:model-value', false);
}

const onSubmit = () => {
  console.log('Form submitted:', formData.value)
  // emit('submit', formData.value)
}

const onReset = () => {
  const resetData = {
    pid: props.user?.pid || 0,
    firstName: props.user?.firstName || '',
    lastName: props.user?.lastName || '',
    position: props.user?.position || '',
    email: props.user?.email || '',
    homePhone: props.user?.homePhone || '',
    notes: props.user?.notes || ''
  }
  formData.value = resetData
  selectedParent.value = null
  // emit('reset')
}

// Search staff by last name
const searchStaff = async (val: string) => {
  if (val.length < 2) {
    staffOptions.value = []
    return
  }

  try {
    loadingStaff.value = true

    const response = await Api.get('/api/staff', {
      params: {
        search: val,
        limit: 10
      }
    })

    if (response.data.success && response.data.data) {
      // Filter out current user if editing
      staffOptions.value = response.data.data
        .filter((staff: IStaff) => staff.id !== props.user?.id)
        .map((staff: IStaff) => ({
          ...staff,
          displayName: `${staff.lastName} ${staff.firstName}`
        }))
    } else {
      staffOptions.value = []
    }
  } catch (error) {
    console.error('Error searching staff:', error)
    staffOptions.value = []
  } finally {
    loadingStaff.value = false
  }
}

// Handle search input with debounce
let searchTimeout: NodeJS.Timeout | null = null
const onSearchInput = (val: string | number | null) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  // Convert value to string and handle null/undefined cases
  const searchValue = val ? String(val) : ''
  
  searchTimeout = setTimeout(() => {
    void searchStaff(searchValue)
  }, 300)
}

// Handle parent selection
const onParentSelect = (staff: IStaff | null) => {
  if (staff) {
    // Prevent selecting self as parent
    if (staff.id === props.user?.id) {
      $q.notify({
        type: 'warning',
        message: 'Нельзя выбрать самого себя в качестве родителя',
        position: 'top'
      })
      selectedParent.value = null
      formData.value.pid = 0
      return
    }
    
    formData.value.pid = staff.id || 0
    selectedParent.value = staff
  } else {
    formData.value.pid = 0
    selectedParent.value = null
  }
}

/* ------------------------------- Хуки и watcher-ы ------------------------------- */
watch(() => props.modelValue, (newVal) => {
  dialog.value = newVal;
});

watch(() => props.user, async (newVal) => {
  // Check if newVal is an object and clone it to avoid reactivity issues
  if (newVal && typeof newVal === 'object') {
    formData.value = { ...newVal };

    // Load parent staff info if pid exists
    if (newVal.pid && newVal.pid > 0) {
      try {
        const response = await Api.get(`/api/staff/${newVal.pid}`)
        if (response.data.success && response.data.data) {
          selectedParent.value = {
            ...response.data.data,
            displayName: `${response.data.data.lastName} ${response.data.data.firstName}`
          }
        }
      } catch (error) {
        console.error('Error loading parent staff:', error)
        selectedParent.value = null
      }
    } else {
      selectedParent.value = null
    }
  } else {
    formData.value = newVal;
    selectedParent.value = null
  }
});
</script>

<style scoped lang="scss">
.q-form {
  max-width: 800px;
  margin: 0 auto;
}
</style>
