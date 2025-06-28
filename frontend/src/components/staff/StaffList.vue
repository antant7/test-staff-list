<template>
  <div class="staff-list">
    <q-table
      :rows="staffList"
      :columns="columns"
      row-key="id"
      v-model:pagination="pagination"
      :rows-per-page-options="[25, 50, 75, 100]"
      :loading="loading"
      @request="onRequest"
      class="staff-table"
      flat
    >
      <template #top>
        <div class="flex justify-between full-width q-mb-md">
          <div class="flex q-gutter-md items-center">
            <q-input
              v-model="searchFilters.id"
              outlined
              dense
              label="Поиск по ID"
              clearable
              style="min-width: 150px"
              @keyup.enter="performSearch"
            />

            <q-input
              v-model="searchFilters.last_name"
              outlined
              dense
              label="Поиск по Фамилии"
              clearable
              style="min-width: 150px"
              @keyup.enter="performSearch"
            />

            <q-input
              v-model="searchFilters.email"
              outlined
              dense
              label="Поиск по Email"
              clearable
              style="min-width: 200px"
              @keyup.enter="performSearch"
            />

            <q-btn
              @click="performSearch"
              color="primary"
              icon="search"
              label="Поиск"
              outline
              no-caps
            />
            <q-btn
              @click="clearFilters"
              color="pink"
              icon="clear"
              label="Очистить"
              outline
              no-caps
            />
          </div>
          <div>
            <q-btn @click="addNewUser" label="Добавить" icon="add" outline no-caps />
          </div>
        </div>
      </template>

      <!-- Custom column for edit button -->
      <template #body-cell-edit="props">
        <q-td :props="props">
          <q-btn
            outline
            color="primary"
            icon="edit"
            @click="editUser(props.row)"
          >
            <q-tooltip>Редактировать</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <!-- Custom column for delete button -->
      <template #body-cell-delete="props">
        <q-td :props="props">
          <q-btn
            outline
            color="negative"
            icon="delete"
            @click="deleteUser(props.row)"
          >
            <q-tooltip>Удалить</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <!-- Слот загрузки -->
      <template v-slot:loading>
        <q-inner-loading showing>
          <q-spinner-gears size="50px" color="primary"/>
        </q-inner-loading>
      </template>

    </q-table>

    <StaffForm v-model="dialog" v-model:user="user" @user-saved="onUserSaved" />
  </div>
</template>

<script setup lang="ts">
/* ------------------------------------ Импорт ------------------------------------ */
import {onMounted, ref} from 'vue';
import type { QTableColumn } from 'quasar';
import { Dialog, Notify } from 'quasar';

import {Api } from 'boot/api-axios';

import type { IStaff } from 'src/types/staff';

import StaffForm from "components/staff/StaffForm.vue";

/* ----------------------------- Интерфейсы и типы ---------------------------- */

/* ----------------------------- Объявление переменных ---------------------------- */
const dialog = ref(false);
const loading = ref(false);
const user = ref<IStaff>({});
const modelFilters = ref<Record<string, unknown>>({});
const searchFilters = ref({
  id: '',
  last_name: '',
  email: ''
});

const pagination = ref({
  page: 1,
  rowsPerPage: 25,
  sortBy: 'id',
  descending: false,
  rowsNumber: 0
});

// Sample data for demonstration
const staffList = ref<IStaff[]>([]);

const columns: QTableColumn[] = [
  {
    name: 'id',
    required: true,
    label: 'ID',
    align: 'left',
    field: 'id',
    sortable: true
  },
  {
    name: 'firstName',
    required: true,
    label: 'Имя',
    align: 'left',
    field: 'firstName',
  },
  {
    name: 'lastName',
    required: true,
    label: 'Фамилия',
    align: 'left',
    field: 'lastName',
  },
  {
    name: 'position',
    label: 'Должность',
    align: 'left',
    field: 'position',
  },
  {
    name: 'email',
    label: 'Email',
    align: 'left',
    field: 'email',
    sortable: true
  },
  {
    name: 'homePhone',
    label: 'Домашний телефон',
    align: 'left',
    field: 'homePhone'
  },
  {
    name: 'notes',
    label: 'Заметки',
    align: 'left',
    field: 'notes'
  },
  {
    name: 'edit',
    label: 'Редактировать',
    align: 'center',
    field: 'edit'
  },
  {
    name: 'delete',
    label: 'Удалить',
    align: 'center',
    field: 'delete'
  }
];

/* ------------------------------- Функции (методы) ------------------------------- */
function performSearch() {
  // Reset to first page when filtering
  pagination.value.page = 1;

  // Update modelFilters with search values
  modelFilters.value = {
    ...modelFilters.value,
    id: searchFilters.value.id || undefined,
    last_name: searchFilters.value.last_name || undefined,
    email: searchFilters.value.email || undefined
  };

  // Remove undefined values
  Object.keys(modelFilters.value).forEach(key => {
    if (modelFilters.value[key] === undefined || modelFilters.value[key] === '') {
      delete modelFilters.value[key];
    }
  });

  apiGetItems();
}

function clearFilters() {
  // Clear search inputs
  searchFilters.value.id = '';
  searchFilters.value.last_name = '';
  searchFilters.value.email = '';

  // Clear model filters
  modelFilters.value = {};

  // Reset to first page
  pagination.value.page = 1;

  // Reload data without filters
  apiGetItems();
}

function apiGetItems() {
  loading.value = true;

  // Prepare query parameters for GET request
  const params = new URLSearchParams();

  // Add filters to query string
  if (modelFilters.value && Object.keys(modelFilters.value).length > 0) {
    Object.entries(modelFilters.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        // Ensure value is properly converted to string
        const stringValue = typeof value === 'object' && value !== null 
          ? JSON.stringify(value) 
          // eslint-disable-next-line @typescript-eslint/no-base-to-string
          : String(value);
        params.append(`filters[${key}]`, stringValue);
      }
    });
  }

  // Add pagination parameters
  if (pagination.value.page) {
    params.append('page', String(pagination.value.page));
  }
  if (pagination.value.rowsPerPage) {
    params.append('limit', String(pagination.value.rowsPerPage));
  }
  if (pagination.value.sortBy) {
    params.append('sort_by', pagination.value.sortBy);
  }
  if (pagination.value.descending !== undefined) {
    params.append('descending', pagination.value.descending ? 'true' : 'false');
  }

  const queryString = params.toString();
  const url = queryString ? `/api/staff?${queryString}` : '/api/staff';

  Api
    .get(url)
    .then((response) => {
        staffList.value = response.data.data;
        // Update total count for pagination
        if (response.data.pagination.total !== undefined) {
          pagination.value.rowsNumber = response.data.pagination.total;
        }
    })
    .catch((error) => {
      console.error('Ошибка загрузки данных:', error);
    })
    .finally(() => {
      loading.value = false;
    });
}

function addNewUser() {
  dialog.value = true;
  user.value = {};
}

function editUser(staff: IStaff) {
  dialog.value = true;
  user.value = staff;
  console.log('Edit staff:', staff, dialog);
}

function deleteUser(staff: IStaff) {
  Dialog.create({
    title: 'Подтверждение удаления',
    message: `Вы уверены, что хотите удалить сотрудника ${staff.firstName} ${staff.lastName}?`,
    cancel: {
      label: 'Отмена',
      color: 'primary',
      outline: true,
      icon: 'close'
    },
    ok: {
      label: 'Удалить',
      color: 'negative',
      outline: true,
      icon: 'delete'
    },
    persistent: true
  }).onOk(() => {
    // Perform delete API call
    Api.delete(`/api/staff/${staff.id}`)
      .then((response) => {
        if (response.data.success) {
          // Remove from local list without reloading
          const index = staffList.value.findIndex(s => s.id === staff.id);
          if (index > -1) {
            staffList.value.splice(index, 1);
          }

          // Update pagination total count
          if (pagination.value.rowsNumber > 0) {
            pagination.value.rowsNumber--;
          }

          // Show success notification
          Notify.create({
            type: 'positive',
            message: 'Сотрудник успешно удален',
            position: 'top-right'
          });
        } else {
          throw new Error(response.data.message || 'Ошибка при удалении');
        }
      })
      .catch((error) => {
        console.error('Error deleting staff:', error);

        // Show error notification
        Notify.create({
          type: 'negative',
          message: error.response?.data?.message || 'Ошибка при удалении сотрудника',
          position: 'top-right'
        });
      });
  });
}

function onRequest(props: { pagination: { page: number; rowsPerPage: number; sortBy: string; descending: boolean } }) {
  const { page, rowsPerPage, sortBy, descending } = props.pagination;

  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  pagination.value.sortBy = sortBy;
  pagination.value.descending = descending;

  apiGetItems();
}

function onUserSaved(savedUser: IStaff) {
  // Update only the specific record in the staff list
  if (savedUser.id) {
    // Update existing user
    const index = staffList.value.findIndex(staff => staff.id === savedUser.id);
    if (index !== -1) {
      staffList.value[index] = { ...savedUser };
    }
  } else {
    // Add new user to the list
    staffList.value.push(savedUser);
  }
}

/* ------------------------------- Хуки и watcher-ы ------------------------------- */
onMounted(() => {
  // Initial load with current pagination settings
  onRequest({ pagination: pagination.value });
});
</script>

<style scoped lang="scss">
.staff-list {
  padding: 16px;
}

.staff-table {
  .q-table__title {
    font-size: 1.2rem;
    font-weight: 600;
    color: $primary;
  }
}
</style>
