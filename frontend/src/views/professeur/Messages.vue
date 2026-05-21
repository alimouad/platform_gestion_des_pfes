<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'

const user       = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const contacts   = ref([])
const selected   = ref(null)
const messages   = ref([])
const body       = ref('')
const loading    = ref(false)
const sending    = ref(false)
const chatBox    = ref(null)

const myUserId = computed(() => user.value?.id)

async function loadContacts() {
  const me = await api.get('/me')
  user.value = me.data?.data || {}
  localStorage.setItem('admin_user', JSON.stringify(user.value))
  const res = await api.get('/messages/contacts')
  contacts.value = res.data.data || []
}

async function selectContact(c) {
  selected.value = c
  messages.value = []
  const res = await api.get(`/messages/${c.user.id}`)
  messages.value = res.data.data || []
  // mark unread as 0 locally
  c.unread = 0
  scrollToBottom()
}

async function send() {
  if (!body.value.trim() || !selected.value) return
  sending.value = true
  try {
    const res = await api.post('/messages', { to_user_id: selected.value.user.id, body: body.value.trim() })
    messages.value.push(res.data.data)
    body.value = ''
    scrollToBottom()
  } catch (e) { console.error(e) }
  sending.value = false
}

async function pollMessages() {
  if (!selected.value) { await loadContacts(); return }
  const [c, m] = await Promise.all([
    api.get('/messages/contacts'),
    api.get(`/messages/${selected.value.user.id}`),
  ])
  contacts.value = c.data.data || []
  const prev = messages.value.length
  messages.value = m.data.data || []
  if (messages.value.length > prev) scrollToBottom()
  const found = contacts.value.find(x => x.user.id === selected.value.user.id)
  if (found) found.unread = 0
}

function scrollToBottom() {
  nextTick(() => { if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight })
}

function formatTime(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  const today = new Date()
  if (d.toDateString() === today.toDateString())
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function initials(u) {
  return ((u?.prenom?.[0] || '') + (u?.nom?.[0] || '')).toUpperCase()
}

let pollTimer = null
onMounted(async () => {
  loading.value = true
  await loadContacts()
  loading.value = false
  pollTimer = setInterval(pollMessages, 5000)
})
onUnmounted(() => clearInterval(pollTimer))

function onKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send() }
}
</script>

<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="relative">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Messagerie</p>
        <h1 class="mt-1 text-2xl font-black">Messages étudiants</h1>
        <p class="mt-1 text-sm text-white/60">{{ contacts.length }} conversation{{ contacts.length !== 1 ? 's' : '' }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[300px_1fr]" style="height: calc(100vh - 14rem)">

      <!-- Contacts list -->
      <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-slate-100">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Conversations</p>
        </div>
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Chargement…</div>
          <div v-else-if="contacts.length === 0" class="p-6 text-center text-sm text-slate-400">
            <i class="fa-regular fa-comment-dots text-3xl text-slate-200 mb-2 block"></i>
            Aucun message reçu
          </div>
          <button v-for="c in contacts" :key="c.user.id" @click="selectContact(c)"
            :class="[
              'w-full flex items-center gap-3 px-5 py-3.5 text-left border-b border-slate-50 transition',
              selected?.user?.id === c.user.id ? 'bg-[#f0f5e0]' : 'hover:bg-slate-50'
            ]">
            <div class="relative shrink-0">
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#1e4a49] text-[#d6e87a] font-black text-sm">
                {{ initials(c.user) }}
              </div>
              <span v-if="c.unread > 0"
                class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white">
                {{ c.unread }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-black text-slate-900 truncate">{{ c.user.prenom }} {{ c.user.nom }}</p>
              <p class="text-xs text-slate-400 truncate">{{ c.last_message?.body }}</p>
            </div>
            <p class="text-[10px] text-slate-300 shrink-0">{{ formatTime(c.last_message?.created_at) }}</p>
          </button>
        </div>
      </div>

      <!-- Chat panel -->
      <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden flex flex-col">

        <!-- No selection -->
        <div v-if="!selected" class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <i class="fa-solid fa-comments text-5xl text-slate-200 mb-4"></i>
            <p class="font-black text-slate-600">Sélectionnez une conversation</p>
          </div>
        </div>

        <template v-else>
          <!-- Contact bar -->
          <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 shrink-0">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#1e4a49] text-[#d6e87a] font-black text-sm">
              {{ initials(selected.user) }}
            </div>
            <div>
              <p class="font-black text-slate-900 text-sm">{{ selected.user.prenom }} {{ selected.user.nom }}</p>
              <p class="text-xs text-slate-400">{{ selected.user.courriel }}</p>
            </div>
          </div>

          <!-- Messages -->
          <div ref="chatBox" class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
            <div v-if="messages.length === 0" class="text-center text-sm text-slate-400 py-8">
              <i class="fa-regular fa-comment-dots text-3xl text-slate-200 mb-2 block"></i>
              Aucun message
            </div>
            <div v-for="msg in messages" :key="msg.id"
              :class="msg.from_user_id === myUserId ? 'flex justify-end' : 'flex justify-start'">
              <div :class="[
                'max-w-[70%] rounded-2xl px-4 py-2.5 shadow-sm',
                msg.from_user_id === myUserId
                  ? 'bg-[#1e4a49] text-white rounded-br-sm'
                  : 'bg-slate-100 text-slate-800 rounded-bl-sm'
              ]">
                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.body }}</p>
                <p :class="['text-[10px] mt-1 text-right', msg.from_user_id === myUserId ? 'text-white/50' : 'text-slate-400']">
                  {{ formatTime(msg.created_at) }}
                  <i v-if="msg.from_user_id === myUserId"
                    :class="['fa-solid fa-check ml-1', msg.read_at ? 'text-[#d6e87a]' : 'text-white/40']"></i>
                </p>
              </div>
            </div>
          </div>

          <!-- Input -->
          <div class="px-6 py-4 border-t border-slate-100 shrink-0">
            <div class="flex items-end gap-3">
              <textarea
                v-model="body"
                @keydown="onKeydown"
                placeholder="Répondre… (Entrée pour envoyer)"
                rows="1"
                class="flex-1 resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none focus:border-[#d6e87a] focus:bg-white transition max-h-32"
                style="field-sizing: content"
              ></textarea>
              <button @click="send" :disabled="!body.trim() || sending"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#1e4a49] text-[#d6e87a] shadow transition hover:bg-[#2d6b5e] disabled:opacity-40">
                <i v-if="sending" class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                <i v-else class="fa-solid fa-paper-plane text-sm"></i>
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
