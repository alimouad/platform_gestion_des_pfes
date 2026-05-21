<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'

const user       = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const encadrant  = ref(null)   // professeur user object
const messages   = ref([])
const body       = ref('')
const loading    = ref(false)
const sending    = ref(false)
const chatBox    = ref(null)

const myUserId = computed(() => user.value?.id)
const encadrantUserId = computed(() => encadrant.value?.id)

async function load() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    // Find accepted project to get encadrant
    const [pr, po] = await Promise.all([api.get('/projets'), api.get('/postulations')])
    const etudiantId = user.value?.etudiant?.id
    const accepted = (po.data.data || []).find(p => p.etudiant_id === etudiantId && p.statut === 'accepte')

    if (accepted) {
      const projet = (pr.data.data || []).find(p => p.id === accepted.projet_id)
      encadrant.value = projet?.professeur?.utilisateur || null
    }

    if (encadrantUserId.value) {
      await loadMessages()
    }
  } catch (e) { console.error(e) }
  loading.value = false
}

async function loadMessages() {
  if (!encadrantUserId.value) return
  const res = await api.get(`/messages/${encadrantUserId.value}`)
  messages.value = res.data.data || []
  scrollToBottom()
}

async function send() {
  if (!body.value.trim() || !encadrantUserId.value) return
  sending.value = true
  try {
    const res = await api.post('/messages', { to_user_id: encadrantUserId.value, body: body.value.trim() })
    messages.value.push(res.data.data)
    body.value = ''
    scrollToBottom()
  } catch (e) { console.error(e) }
  sending.value = false
}

function scrollToBottom() {
  nextTick(() => {
    if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight
  })
}

function formatTime(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  const today = new Date()
  if (d.toDateString() === today.toDateString())
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

let pollTimer = null
onMounted(async () => {
  await load()
  pollTimer = setInterval(loadMessages, 5000)
})
onUnmounted(() => clearInterval(pollTimer))

function onKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send() }
}
</script>

<template>
  <div class="space-y-5 h-full flex flex-col" style="height: calc(100vh - 8rem)">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden shrink-0">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="relative">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Messagerie</p>
        <h1 class="mt-1 text-2xl font-black">Mon encadrant</h1>
        <p class="mt-1 text-sm text-white/60">
          <span v-if="encadrant">{{ encadrant.prenom }} {{ encadrant.nom }}</span>
          <span v-else>Aucun encadrant assigné</span>
        </p>
      </div>
    </div>

    <!-- No encadrant -->
    <div v-if="!loading && !encadrant" class="flex-1 flex items-center justify-center rounded-3xl border border-white/70 bg-white/90">
      <div class="text-center px-8">
        <i class="fa-solid fa-comments text-5xl text-slate-200 mb-4"></i>
        <p class="font-black text-slate-700">Pas encore d'encadrant</p>
        <p class="text-sm text-slate-400 mt-1">Vous devez être assigné à un projet pour contacter votre encadrant.</p>
      </div>
    </div>

    <!-- Chat -->
    <div v-else-if="encadrant" class="flex-1 flex flex-col rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden min-h-0">

      <!-- Contact bar -->
      <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 shrink-0">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#1e4a49] text-[#d6e87a] font-black text-sm">
          {{ (encadrant.prenom?.[0] || '') + (encadrant.nom?.[0] || '') }}
        </div>
        <div>
          <p class="font-black text-slate-900 text-sm">{{ encadrant.prenom }} {{ encadrant.nom }}</p>
          <p class="text-xs text-slate-400">{{ encadrant.courriel }}</p>
        </div>
        <div class="ml-auto flex items-center gap-1.5">
          <span class="h-2 w-2 rounded-full bg-green-400"></span>
          <span class="text-xs text-slate-400 font-semibold">Encadrant</span>
        </div>
      </div>

      <!-- Messages -->
      <div ref="chatBox" class="flex-1 overflow-y-auto px-6 py-4 space-y-3 min-h-0">
        <div v-if="loading" class="text-center text-sm text-slate-400 py-8">Chargement…</div>
        <div v-else-if="messages.length === 0" class="text-center text-sm text-slate-400 py-8">
          <i class="fa-regular fa-comment-dots text-3xl text-slate-200 mb-2 block"></i>
          Commencez la conversation !
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
            placeholder="Écrivez votre message… (Entrée pour envoyer)"
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
    </div>

  </div>
</template>
