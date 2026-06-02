import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const notifications = ref([])
const unread = ref(0)
let pollInterval = null

async function fetchNotifications() {
  try {
    const res = await api.get('/notifications')
    notifications.value = res.data.data
    unread.value = res.data.unread
  } catch {}
}

async function markRead(id) {
  try {
    await api.post(`/notifications/${id}/read`)
    const n = notifications.value.find(n => n.id === id)
    if (n) { n.lue_le = new Date().toISOString(); unread.value = Math.max(0, unread.value - 1) }
  } catch {}
}

async function markAllRead() {
  try {
    await api.post('/notifications/read-all')
    notifications.value.forEach(n => { if (!n.lue_le) n.lue_le = new Date().toISOString() })
    unread.value = 0
  } catch {}
}

export function useNotifications() {
  onMounted(() => {
    fetchNotifications()
    pollInterval = setInterval(fetchNotifications, 30000)
  })
  onUnmounted(() => clearInterval(pollInterval))

  return {
    notifications,
    unread,
    fetchNotifications,
    markRead,
    markAllRead,
  }
}
