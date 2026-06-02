<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const loading = ref(false)
const stats = ref({ postulations: 0, depots: 0, valide: 0 })

async function refresh() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const etudiantId = user.value?.etudiant?.id
    if (etudiantId) {
      const [po, de] = await Promise.all([
        api.get('/postulations'),
        api.get('/depots'),
      ])
      const myPo = po.data.data.filter(p => p.etudiant_id === etudiantId)
      const myDe = de.data.data.filter(d => d.etudiant_id === etudiantId)
      stats.value = {
        postulations: myPo.length,
        depots:       myDe.length,
        valide:       myDe.filter(d => d.statut_validation === 'valide').length,
      }
    }
  } catch {}
  loading.value = false
}

const initials = computed(() => {
  return ((user.value.prenom || 'E')[0] + (user.value.nom || 'T')[0]).toUpperCase()
})

const memberSince = computed(() => {
  if (!user.value.created_at) return '—'
  return new Date(user.value.created_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

// ── Changement de mot de passe ───────────────────────────────
const pwForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const pwLoading = ref(false)
const pwError   = ref('')
const pwSuccess = ref('')
const showPw    = ref({ current: false, new: false, confirm: false })

async function changePassword() {
  pwError.value   = ''
  pwSuccess.value = ''
  if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
    pwError.value = 'Les mots de passe ne correspondent pas.'
    return
  }
  pwLoading.value = true
  try {
    await api.put('/me/password', pwForm.value)
    pwSuccess.value = 'Mot de passe mis à jour avec succès !'
    pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) {
    pwError.value = e.response?.data?.message || 'Erreur lors de la mise à jour.'
  }
  pwLoading.value = false
}

onMounted(refresh)
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Mon profil</h1>
      <p class="text-sm text-slate-400">Vos informations personnelles</p>
    </div>

    <!-- Profile card -->
    <article class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#1e4a49] via-[#2a5e5d] to-[#3d7a6f] shadow-xl">
      <div class="relative z-10 p-8 text-white">
        <div class="flex flex-wrap items-center gap-6">
          <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-3xl bg-[#d6e87a] text-3xl font-black text-[#4a5e20] shadow-lg">
            {{ initials }}
          </div>
          <div class="flex-1 min-w-[200px]">
            <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#d6e87a]">
              {{ user.etudiant?.code_etudiant || '—' }}
            </p>
            <h2 class="mt-2 text-3xl font-extrabold leading-tight">
              {{ user.prenom }} {{ user.nom }}
            </h2>
            <p class="mt-1 text-sm text-white/80">{{ user.courriel }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-user-graduate mr-1.5"></i>{{ user.etudiant?.niveau || 'Étudiant' }}
              </span>
              <span v-if="user.etudiant?.filiere" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-tags mr-1.5"></i>{{ user.etudiant.filiere }}
              </span>
              <span v-if="user.departement" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-building-columns mr-1.5"></i>{{ user.departement.nom }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </article>

    <!-- Stats grid -->
    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
          <span>Postulations</span>
          <i class="fa-solid fa-file-signature text-slate-300"></i>
        </div>
        <p class="text-3xl font-extrabold text-slate-900">{{ stats.postulations }}</p>
      </div>
      <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
          <span>Dépôts</span>
          <i class="fa-solid fa-cloud-arrow-up text-slate-300"></i>
        </div>
        <p class="text-3xl font-extrabold text-slate-900">{{ stats.depots }}</p>
      </div>
      <div class="rounded-[2rem] border border-[#d6e87a] bg-[#f8faef] p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-[#6a8a40] mb-2">
          <span>Validés</span>
          <i class="fa-solid fa-circle-check text-[#6a8a40]"></i>
        </div>
        <p class="text-3xl font-extrabold text-[#1e4a49]">{{ stats.valide }}</p>
      </div>
    </div>

    <!-- Info table -->
    <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div class="px-6 pt-5 pb-3">
        <p class="text-base font-extrabold">Informations</p>
        <p class="text-xs text-slate-400">Détails du compte</p>
      </div>
      <dl class="divide-y divide-slate-100">
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Nom complet</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.prenom }} {{ user.nom }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Email</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.courriel }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Code étudiant</dt>
          <dd class="col-span-2 text-sm font-mono font-bold text-slate-800">{{ user.etudiant?.code_etudiant || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Filière</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.etudiant?.filiere || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Niveau</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.etudiant?.niveau || '—' }}</dd>
        </div>
        <div v-if="user.etudiant?.groupe" class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Groupe</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.etudiant.groupe }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Département</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.departement?.nom || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Membre depuis</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800 capitalize">{{ memberSince }}</dd>
        </div>
      </dl>
    </article>

    <!-- Change password -->
    <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div class="px-6 pt-5 pb-3 flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1e4a49]">
          <i class="fa-solid fa-lock text-[#d6e87a] text-sm"></i>
        </div>
        <div>
          <p class="text-base font-extrabold text-slate-900">Changer mon mot de passe</p>
          <p class="text-xs text-slate-400">Choisissez un mot de passe sécurisé d'au moins 8 caractères</p>
        </div>
      </div>
      <form @submit.prevent="changePassword" class="px-6 pb-6 space-y-4 mt-2">
        <div v-if="pwError" class="flex items-center gap-2 rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
          <i class="fa-solid fa-circle-exclamation shrink-0"></i> {{ pwError }}
        </div>
        <div v-if="pwSuccess" class="flex items-center gap-2 rounded-2xl bg-green-50 border border-green-100 px-4 py-3 text-sm text-green-700">
          <i class="fa-solid fa-circle-check shrink-0"></i> {{ pwSuccess }}
        </div>
        <div>
          <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Mot de passe actuel</label>
          <div class="relative">
            <input v-model="pwForm.current_password" :type="showPw.current ? 'text' : 'password'" required
              class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm outline-none focus:border-[#1e4a49] focus:bg-white transition" />
            <button type="button" @click="showPw.current = !showPw.current"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition">
              <i :class="showPw.current ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
        </div>
        <div>
          <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Nouveau mot de passe</label>
          <div class="relative">
            <input v-model="pwForm.new_password" :type="showPw.new ? 'text' : 'password'" required minlength="8"
              class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm outline-none focus:border-[#1e4a49] focus:bg-white transition" />
            <button type="button" @click="showPw.new = !showPw.new"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition">
              <i :class="showPw.new ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
        </div>
        <div>
          <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Confirmer le mot de passe</label>
          <div class="relative">
            <input v-model="pwForm.new_password_confirmation" :type="showPw.confirm ? 'text' : 'password'" required
              class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm outline-none focus:border-[#1e4a49] focus:bg-white transition"
              :class="pwForm.new_password_confirmation && pwForm.new_password !== pwForm.new_password_confirmation ? 'border-red-300' : ''" />
            <button type="button" @click="showPw.confirm = !showPw.confirm"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition">
              <i :class="showPw.confirm ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
          <p v-if="pwForm.new_password_confirmation && pwForm.new_password !== pwForm.new_password_confirmation"
            class="mt-1 text-[11px] text-red-500">Les mots de passe ne correspondent pas</p>
        </div>
        <button type="submit" :disabled="pwLoading"
          class="w-full rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163836] transition flex items-center justify-center gap-2 disabled:opacity-50">
          <i v-if="pwLoading" class="fa-solid fa-circle-notch fa-spin"></i>
          <i v-else class="fa-solid fa-key"></i>
          {{ pwLoading ? 'Mise à jour…' : 'Mettre à jour le mot de passe' }}
        </button>
      </form>
    </article>

    <!-- Help card -->
    <article class="rounded-[2rem] border border-dashed border-slate-200 bg-white/60 p-6">
      <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
          <i class="fa-solid fa-circle-info text-lg"></i>
        </div>
        <div>
          <p class="text-sm font-extrabold text-slate-800">Modifier mes informations</p>
          <p class="mt-1 text-xs text-slate-500 leading-relaxed">
            Pour mettre à jour votre nom, courriel ou autres informations académiques,
            contactez votre coordinateur ou l'administration.
          </p>
        </div>
      </div>
    </article>
  </div>
</template>
