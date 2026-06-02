<script setup>
import { onMounted, ref } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { nom: '', prenom: '', courriel: '', mot_de_passe: '', role: 'etudiant', departement_id: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('users', defaultForm)
const currentUserId = JSON.parse(localStorage.getItem('admin_user') || '{}')?.id

const departements = ref([])

// ── Import Excel ─────────────────────────────────────────────
const showImport = ref(false)
const importFile  = ref(null)
const importing   = ref(false)
const importResult = ref(null)

function downloadTemplate() {
  window.open(`${api.defaults.baseURL}/import-users/template`, '_blank')
}

function copyCredentials() {
  if (!importResult.value?.users?.length) return
  const text = importResult.value.users
    .map(u => `${u.prenom} ${u.nom} | ${u.courriel} | ${u.password}`)
    .join('\n')
  navigator.clipboard.writeText(text)
  alert('Credentials copiés dans le presse-papiers !')
}

function exportCredentialsCsv() {
  if (!importResult.value?.users?.length) return
  const rows = [
    ['Prénom', 'Nom', 'Email', 'Rôle', 'Mot de passe temporaire'],
    ...importResult.value.users.map(u => [u.prenom, u.nom, u.courriel, u.role, u.password])
  ]
  const csv = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href = url
  a.download = 'credentials_import.csv'
  a.click()
  URL.revokeObjectURL(url)
}

async function handleImport() {
  if (!importFile.value) return
  importing.value = true
  importResult.value = null
  try {
    const fd = new FormData()
    fd.append('file', importFile.value)
    const res = await api.post('/import-users', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    importResult.value = res.data
    await fetchAll()
  } catch (e) {
    importResult.value = { error: e.response?.data?.message || 'Erreur lors de l\'import' }
  }
  importing.value = false
}

onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/departements')
    departements.value = res.data.data
  } catch {}
})

const roleColors = {
  superadmin: 'bg-[#d6e87a] text-slate-800',
  coordinateur: 'bg-purple-100 text-purple-700',
  professeur: 'bg-blue-100 text-blue-700',
  etudiant: 'bg-slate-100 text-slate-600',
}
const roleLabel = {
  superadmin: 'Super Admin',
  coordinateur: 'Coordinateur',
  professeur: 'Professeur',
  etudiant: 'Étudiant',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Utilisateurs</h1>
        <p class="text-sm text-slate-400">Gestion des comptes utilisateurs</p>
      </div>
      <div class="flex gap-2">
        <button @click="showImport = true"
          class="flex items-center gap-2 rounded-2xl border border-[#1e4a49] bg-[#f0f3eb] px-5 py-2.5 text-sm font-bold text-[#1e4a49] hover:bg-[#d6e87a] transition">
          <i class="fa-solid fa-file-excel"></i> Importer Excel
        </button>
        <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
          <i class="fa-solid fa-plus"></i> Nouvel utilisateur
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un utilisateur…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <!-- Table -->
    <div class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-sm text-slate-400">Chargement…</div>
      <div v-else-if="filtered.length === 0" class="p-10 text-center text-sm text-slate-400">Aucun utilisateur trouvé</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <th class="px-6 py-4">Nom</th>
            <th class="px-4 py-4">Email</th>
            <th class="px-4 py-4">Rôle</th>
            <th class="px-4 py-4">Département</th>
            <th class="px-4 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in filtered" :key="u.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
            <td class="px-6 py-3.5">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-xs font-black text-slate-700">
                  {{ (u.prenom || 'U')[0] }}{{ (u.nom || '')[0] }}
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ u.prenom }} {{ u.nom }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ u.courriel }}</td>
            <td class="px-4 py-3.5">
              <span class="rounded-lg px-2.5 py-1 text-[11px] font-bold" :class="roleColors[u.role] || 'bg-slate-100 text-slate-600'">
                {{ roleLabel[u.role] || u.role }}
              </span>
            </td>
            <td class="px-4 py-3.5 text-slate-500">{{ u.departement?.nom || '—' }}</td>
            <td class="px-4 py-3.5 text-right">
              <button @click="openEdit(u)" class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button v-if="u.id !== currentUserId" @click="remove(u.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash"></i>
              </button>
              <span v-else class="rounded-xl bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-300 cursor-not-allowed" title="Impossible de supprimer votre propre compte">
                <i class="fa-solid fa-trash"></i>
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouvel' }} utilisateur</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Prénom</label>
                <input v-model="form.prenom" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Nom</label>
                <input v-model="form.nom" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Email</label>
              <input v-model="form.courriel" type="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Mot de passe {{ editing ? '(laisser vide = inchangé)' : '' }}</label>
              <input v-model="form.mot_de_passe" type="password" :required="!editing" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Rôle</label>
                <select v-model="form.role" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option value="etudiant">Étudiant</option>
                  <option value="professeur">Professeur</option>
                  <option value="coordinateur">Coordinateur</option>
                  <option value="superadmin">Super Admin</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Département</label>
                <select v-model="form.departement_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                  <option value="">— Aucun —</option>
                  <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Import Excel Modal ── -->
    <Teleport to="body">
      <div v-if="showImport" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Import en masse</p>
              <h2 class="text-base font-black text-white">Importer des utilisateurs</h2>
            </div>
            <button @click="showImport = false; importResult = null; importFile = null"
              class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-6 space-y-5">

            <!-- Instructions -->
            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 space-y-2">
              <p class="text-xs font-black text-slate-700">Format du fichier Excel (.xlsx)</p>
              <div class="grid grid-cols-3 gap-1 text-[10px] font-bold text-slate-500">
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">A — Nom</span>
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">B — Prénom</span>
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">C — Email</span>
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">D — Rôle</span>
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">E — Filière</span>
                <span class="rounded-lg bg-white border border-slate-200 px-2 py-1">F — Département</span>
              </div>
              <p class="text-[10px] text-slate-400">Rôles valides : <code class="bg-slate-100 px-1 rounded">etudiant</code> <code class="bg-slate-100 px-1 rounded">professeur</code> <code class="bg-slate-100 px-1 rounded">coordinateur</code></p>
              <button @click="downloadTemplate"
                class="flex items-center gap-2 text-xs font-black text-[#1e4a49] hover:underline">
                <i class="fa-solid fa-download text-[#d6e87a]"></i> Télécharger le template Excel
              </button>
            </div>

            <!-- File input -->
            <div>
              <label class="mb-2 block text-[11px] font-black uppercase tracking-widest text-slate-400">Fichier Excel</label>
              <input type="file" accept=".xlsx,.xls,.csv"
                @change="e => importFile = e.target.files[0]"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-[#1e4a49] file:px-4 file:py-1.5 file:text-xs file:font-black file:text-white hover:file:bg-[#163836] transition" />
            </div>

            <!-- Result -->
            <div v-if="importResult" class="rounded-2xl border p-4 space-y-3"
              :class="importResult.error ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50'">
              <div v-if="importResult.error" class="text-sm font-bold text-red-600">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ importResult.error }}
              </div>
              <template v-else>
                <div class="flex items-center gap-4">
                  <div class="text-center">
                    <p class="text-2xl font-black text-green-700">{{ importResult.created }}</p>
                    <p class="text-[10px] font-bold text-green-600 uppercase">Créés</p>
                  </div>
                  <div class="text-center">
                    <p class="text-2xl font-black text-slate-500">{{ importResult.skipped }}</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Ignorés</p>
                  </div>
                  <div class="text-center">
                    <p class="text-2xl font-black text-red-500">{{ importResult.errors?.length }}</p>
                    <p class="text-[10px] font-bold text-red-400 uppercase">Erreurs</p>
                  </div>
                </div>
                <!-- Credentials table -->
                <div v-if="importResult.users?.length" class="mt-3">
                  <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                      Mots de passe temporaires
                    </p>
                    <div class="flex gap-2">
                      <button @click="copyCredentials"
                        class="flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-[10px] font-black text-slate-600 hover:bg-slate-200 transition">
                        <i class="fa-solid fa-copy text-[9px]"></i> Copier tout
                      </button>
                      <button @click="exportCredentialsCsv"
                        class="flex items-center gap-1.5 rounded-xl bg-[#1e4a49] px-3 py-1.5 text-[10px] font-black text-[#d6e87a] hover:bg-[#163836] transition">
                        <i class="fa-solid fa-file-csv text-[9px]"></i> Exporter CSV
                      </button>
                    </div>
                  </div>
                  <div class="max-h-40 overflow-y-auto rounded-xl border border-green-200 bg-white">
                    <table class="w-full text-xs">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-left font-black text-slate-500">Nom</th>
                          <th class="px-3 py-2 text-left font-black text-slate-500">Email</th>
                          <th class="px-3 py-2 text-left font-black text-slate-500">Mot de passe</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="u in importResult.users" :key="u.courriel" class="border-t border-slate-100">
                          <td class="px-3 py-1.5 font-semibold text-slate-700">{{ u.prenom }} {{ u.nom }}</td>
                          <td class="px-3 py-1.5 text-slate-500">{{ u.courriel }}</td>
                          <td class="px-3 py-1.5 font-mono font-bold text-[#1e4a49]">{{ u.password }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <!-- Errors -->
                <div v-if="importResult.errors?.length" class="mt-2 space-y-1">
                  <p class="text-[10px] font-black uppercase tracking-widest text-red-500">Erreurs</p>
                  <p v-for="e in importResult.errors" :key="e" class="text-xs text-red-600">• {{ e }}</p>
                </div>
              </template>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
              <button @click="showImport = false; importResult = null; importFile = null"
                class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                Fermer
              </button>
              <button @click="handleImport" :disabled="!importFile || importing"
                class="flex-2 rounded-2xl py-3 text-sm font-black text-white transition flex items-center justify-center gap-2"
                :class="importFile && !importing ? 'bg-[#1e4a49] hover:bg-[#163836]' : 'bg-slate-200 text-slate-400 cursor-not-allowed'">
                <i v-if="importing" class="fa-solid fa-circle-notch fa-spin"></i>
                <i v-else class="fa-solid fa-file-import"></i>
                {{ importing ? 'Import en cours…' : 'Lancer l\'import' }}
              </button>
            </div>

          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
