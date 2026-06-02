<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const loading = ref(false)
const stats = ref({ projets: 0, encadres: 0, depotsValides: 0, postulations: 0 })

async function refresh() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const profId = Number(user.value?.professeur?.id)
    if (!profId) { loading.value = false; return }

    const [pr, po, de] = await Promise.all([
      api.get('/projets'),
      api.get('/postulations'),
      api.get('/depots'),
    ])

    const myProjets = (pr.data.data || []).filter(p => Number(p.professeur_id) === profId)
    const ids = myProjets.map(p => Number(p.id))

    stats.value = {
      projets:       myProjets.length,
      encadres:      new Set((po.data.data || []).filter(p => ids.includes(Number(p.projet_id)) && p.statut === 'accepte').map(p => p.etudiant_id)).size,
      depotsValides: (de.data.data || []).filter(d => ids.includes(Number(d.projet_id)) && d.statut_validation === 'valide').length,
      postulations:  (po.data.data || []).filter(p => ids.includes(Number(p.projet_id)) && p.statut === 'en_attente').length,
    }
  } catch (e) {
    console.error('Profil load error:', e?.response?.data || e)
  }
  loading.value = false
}

const initials = computed(() =>
  ((user.value.prenom || 'P')[0] + (user.value.nom || 'R')[0]).toUpperCase()
)

const memberSince = computed(() => {
  if (!user.value.created_at) return '—'
  return new Date(user.value.created_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const infoRows = computed(() => [
  { label: 'Nom complet',   icon: 'fa-user',              value: `${user.value.prenom || ''} ${user.value.nom || ''}`.trim() || '—' },
  { label: 'Email',         icon: 'fa-envelope',          value: user.value.courriel || '—' },
  { label: 'Grade',         icon: 'fa-medal',             value: user.value.professeur?.grade || '—' },
  { label: 'Spécialité',    icon: 'fa-microscope',        value: user.value.professeur?.specialite || '—' },
  { label: 'Bureau',        icon: 'fa-door-open',         value: user.value.professeur?.bureau ? `Bureau ${user.value.professeur.bureau}` : '—' },
  { label: 'Département',   icon: 'fa-building-columns',  value: user.value.departement?.nom || '—' },
  { label: 'Membre depuis', icon: 'fa-calendar-days',     value: memberSince.value },
])

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
</script>

<template>
  <div class="space-y-6">

    <!-- Hero card -->
    <div class="relative overflow-hidden rounded-3xl bg-[#1e4a49] shadow-xl">
      <!-- Decorative blobs -->
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="absolute bottom-0 left-0 h-24 w-24 rounded-full bg-white/5 -translate-x-1/2 translate-y-1/2"></div>

      <div class="relative z-10 p-8">
        <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Profil enseignant</p>

        <div class="mt-5 flex flex-wrap items-center gap-6">
          <!-- Avatar -->
          <div class="relative shrink-0">
            <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-[#d6e87a] text-3xl font-black text-[#1e4a49] shadow-lg">
              {{ initials }}
            </div>
            <div class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-400 border-2 border-[#1e4a49]">
              <i class="fa-solid fa-check text-[8px] text-white"></i>
            </div>
          </div>

          <!-- Name & meta -->
          <div class="flex-1 min-w-[200px]">
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <span v-if="user.professeur?.grade"
                class="rounded-full bg-[#d6e87a]/20 border border-[#d6e87a]/30 px-3 py-0.5 text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">
                {{ user.professeur.grade }}
              </span>
            </div>
            <h2 class="text-3xl font-black text-white leading-tight">
              {{ user.prenom }} {{ user.nom }}
            </h2>
            <p class="mt-1 flex items-center gap-2 text-sm text-white/60">
              <i class="fa-regular fa-envelope text-[#d6e87a]/70"></i>
              {{ user.courriel }}
            </p>

            <!-- Tags -->
            <div class="mt-4 flex flex-wrap gap-2">
              <span v-if="user.professeur?.specialite"
                class="flex items-center gap-1.5 rounded-2xl bg-white/10 px-3 py-1.5 text-xs font-bold text-white/80 backdrop-blur">
                <i class="fa-solid fa-microscope text-[#d6e87a] text-[10px]"></i>
                {{ user.professeur.specialite }}
              </span>
              <span v-if="user.departement"
                class="flex items-center gap-1.5 rounded-2xl bg-white/10 px-3 py-1.5 text-xs font-bold text-white/80 backdrop-blur">
                <i class="fa-solid fa-building-columns text-[#d6e87a] text-[10px]"></i>
                {{ user.departement.nom }}
              </span>
              <span v-if="user.professeur?.bureau"
                class="flex items-center gap-1.5 rounded-2xl bg-white/10 px-3 py-1.5 text-xs font-bold text-white/80 backdrop-blur">
                <i class="fa-solid fa-door-open text-[#d6e87a] text-[10px]"></i>
                Bureau {{ user.professeur.bureau }}
              </span>
              <span class="flex items-center gap-1.5 rounded-2xl bg-white/10 px-3 py-1.5 text-xs font-bold text-white/80 backdrop-blur">
                <i class="fa-solid fa-calendar-days text-[#d6e87a] text-[10px]"></i>
                Depuis {{ memberSince }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom stat strip -->
      <div class="relative z-10 border-t border-white/10 grid grid-cols-4 divide-x divide-white/10">
        <div class="px-6 py-4 text-center">
          <p class="text-2xl font-black text-[#d6e87a]">{{ stats.projets }}</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mt-0.5">Projets</p>
        </div>
        <div class="px-6 py-4 text-center">
          <p class="text-2xl font-black text-[#d6e87a]">{{ stats.encadres }}</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mt-0.5">Encadrés</p>
        </div>
        <div class="px-6 py-4 text-center">
          <p class="text-2xl font-black text-[#d6e87a]">{{ stats.depotsValides }}</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mt-0.5">Dépôts validés</p>
        </div>
        <div class="px-6 py-4 text-center">
          <p class="text-2xl font-black text-[#d6e87a]">{{ stats.postulations }}</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mt-0.5">En attente</p>
        </div>
      </div>
    </div>

    <!-- Info card -->
    <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f5e0] text-[#4a7a30]">
          <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
          <p class="text-sm font-black text-slate-900">Informations du compte</p>
          <p class="text-xs text-slate-400">Données académiques enregistrées</p>
        </div>
      </div>

      <dl class="divide-y divide-slate-100">
        <div v-for="row in infoRows" :key="row.label"
          class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 transition">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
            <i :class="`fa-solid ${row.icon} text-xs`"></i>
          </div>
          <dt class="w-32 shrink-0 text-[11px] font-black uppercase tracking-widest text-slate-400">{{ row.label }}</dt>
          <dd class="flex-1 text-sm font-bold text-slate-800">{{ row.value }}</dd>
        </div>
      </dl>
    </div>

    <!-- Activity hint -->
    <div class="rounded-3xl border border-[#d6e87a]/40 bg-[#f8faef] px-6 py-5 flex items-center gap-4">
      <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#d6e87a] text-[#1e4a49] text-lg">
        <i class="fa-solid fa-lightbulb"></i>
      </div>
      <div>
        <p class="text-sm font-black text-[#1e4a49]">Activité récente</p>
        <p class="text-xs text-slate-500 mt-0.5">
          Vous encadrez <strong>{{ stats.encadres }}</strong> étudiant{{ stats.encadres !== 1 ? 's' : '' }}
          et avez <strong>{{ stats.postulations }}</strong> candidature{{ stats.postulations !== 1 ? 's' : '' }} en attente de traitement.
        </p>
      </div>
    </div>

    <!-- Change password -->
    <article class="rounded-4xl border border-white/70 bg-white/90 shadow-sm overflow-hidden">
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
            <button type="button" @click="showPw.current = !showPw.current" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
              <i :class="showPw.current ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
        </div>
        <div>
          <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Nouveau mot de passe</label>
          <div class="relative">
            <input v-model="pwForm.new_password" :type="showPw.new ? 'text' : 'password'" required minlength="8"
              class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm outline-none focus:border-[#1e4a49] focus:bg-white transition" />
            <button type="button" @click="showPw.new = !showPw.new" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
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
            <button type="button" @click="showPw.confirm = !showPw.confirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
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

  </div>
</template>
