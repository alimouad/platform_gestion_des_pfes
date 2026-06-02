<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({ variant: { type: String, default: 'dark' } })
const router = useRouter()
const { notifications, unread, markRead, markAllRead } = useNotifications()
const open = ref(false)

const typeIcon = {
  postulation_acceptee: { icon: 'fa-circle-check',    color: 'text-green-500',  bg: 'bg-green-50' },
  postulation_rejetee:  { icon: 'fa-circle-xmark',    color: 'text-red-500',    bg: 'bg-red-50' },
  depot_valide:         { icon: 'fa-file-circle-check',color: 'text-green-500',  bg: 'bg-green-50' },
  depot_rejete:         { icon: 'fa-file-circle-xmark',color: 'text-red-500',    bg: 'bg-red-50' },
  soutenance_planifiee: { icon: 'fa-calendar-check',   color: 'text-purple-500', bg: 'bg-purple-50' },
  message_recu:         { icon: 'fa-comment',          color: 'text-blue-500',   bg: 'bg-blue-50' },
}
function ti(type) { return typeIcon[type] || { icon: 'fa-bell', color: 'text-slate-400', bg: 'bg-slate-50' } }

function fmtDate(d) {
  const date = new Date(d)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000)
  if (diff < 60) return 'À l\'instant'
  if (diff < 3600) return `Il y a ${Math.floor(diff/60)} min`
  if (diff < 86400) return `Il y a ${Math.floor(diff/3600)}h`
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

async function handleClick(n) {
  if (!n.lue_le) await markRead(n.id)
  open.value = false
  if (n.lien) router.push(n.lien)
}
</script>

<template>
  <div class="relative">
    <!-- Bell button -->
    <button @click="open = !open"
      class="relative flex h-9 w-9 items-center justify-center rounded-xl transition"
      :class="variant === 'light'
        ? (open ? 'bg-[#1e4a49]/10 text-[#1e4a49]' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 bg-white')
        : (open ? 'bg-[#d6e87a]/20 text-[#d6e87a]' : 'text-white/60 hover:text-white hover:bg-white/10')">
      <i class="fa-solid fa-bell text-sm"></i>
      <span v-if="unread > 0"
        class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white">
        {{ unread > 9 ? '9+' : unread }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      leave-active-class="transition-all duration-150"
      leave-to-class="opacity-0 scale-95 -translate-y-1">
      <div v-if="open"
        class="absolute right-0 top-11 z-50 w-80 rounded-3xl bg-white shadow-2xl border border-slate-100 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-black text-slate-900">Notifications</h3>
            <span v-if="unread > 0" class="rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-black text-white">{{ unread }}</span>
          </div>
          <button v-if="unread > 0" @click="markAllRead"
            class="text-[10px] font-bold text-[#1e4a49] hover:underline">
            Tout marquer lu
          </button>
        </div>

        <!-- List -->
        <div class="max-h-80 overflow-y-auto">
          <div v-if="notifications.length === 0" class="py-10 text-center">
            <i class="fa-solid fa-bell-slash text-3xl text-slate-200"></i>
            <p class="mt-2 text-xs font-bold text-slate-400">Aucune notification</p>
          </div>
          <button v-for="n in notifications" :key="n.id"
            @click="handleClick(n)"
            class="w-full flex items-start gap-3 px-4 py-3 border-b border-slate-50 text-left transition hover:bg-slate-50"
            :class="!n.lue_le ? 'bg-[#f8faef]' : ''">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="ti(n.type).bg">
              <i :class="`fa-solid ${ti(n.type).icon} text-sm ${ti(n.type).color}`"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-slate-800 leading-snug">{{ n.titre }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5 leading-snug line-clamp-2">{{ n.corps }}</p>
              <p class="text-[9px] text-slate-300 mt-1">{{ fmtDate(n.created_at) }}</p>
            </div>
            <div v-if="!n.lue_le" class="h-2 w-2 rounded-full bg-[#1e4a49] shrink-0 mt-1"></div>
          </button>
        </div>

      </div>
    </Transition>

    <!-- Backdrop -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
  </div>
</template>
