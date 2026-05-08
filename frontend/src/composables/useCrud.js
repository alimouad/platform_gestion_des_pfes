import { ref, computed } from 'vue'
import api from '@/services/api'

export function useCrud(endpoint, defaultForm = {}) {
  const items   = ref([])
  const loading = ref(false)
  const search  = ref('')
  const showModal = ref(false)
  const editing   = ref(null)
  const form      = ref({ ...defaultForm })
  const error     = ref('')

  const filtered = computed(() =>
    items.value.filter(item =>
      JSON.stringify(item).toLowerCase().includes(search.value.toLowerCase())
    )
  )

  async function fetchAll() {
    loading.value = true
    error.value = ''
    try {
      const res = await api.get(`/${endpoint}`)
      items.value = res.data.data
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur de chargement'
    }
    loading.value = false
  }

  async function save() {
    error.value = ''
    try {
      if (editing.value) {
        await api.put(`/${endpoint}/${editing.value.id}`, form.value)
      } else {
        await api.post(`/${endpoint}`, form.value)
      }
      await fetchAll()
      closeModal()
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la sauvegarde'
      if (e.response?.data?.errors) {
        error.value = Object.values(e.response.data.errors).flat().join(' | ')
      }
    }
  }

  async function remove(id) {
    if (!confirm('Confirmer la suppression ?')) return
    try {
      await api.delete(`/${endpoint}/${id}`)
      await fetchAll()
    } catch (e) {
      error.value = e.response?.data?.message || 'Erreur lors de la suppression'
    }
  }

  function openCreate() {
    editing.value = null
    form.value = { ...defaultForm }
    error.value = ''
    showModal.value = true
  }

  function openEdit(item) {
    editing.value = item
    form.value = { ...item }
    error.value = ''
    showModal.value = true
  }

  function closeModal() {
    showModal.value = false
    editing.value = null
    error.value = ''
  }

  return {
    items, loading, search, filtered,
    showModal, editing, form, error,
    fetchAll, save, remove,
    openCreate, openEdit, closeModal,
  }
}
