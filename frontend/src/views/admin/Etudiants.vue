<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { user_id: '', code_etudiant: '', filiere_id: '', niveau: 'L3' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('etudiants', defaultForm)

const users    = ref([])
const filieres = ref([])
onMounted(async () => {
  await fetchAll()
  try {
    const [u, f] = await Promise.all([api.get('/users'), api.get('/filieres')])
    users.value    = u.data.data.filter(u => u.role === 'etudiant')
    filieres.value = f.data.data || []
  } catch {}
})

// Group by département → filière
const groupedByDeptFiliere = computed(() => {
  const depts = {}
  for (const e of filtered.value) {
    const dept = e.filiere?.departement?.nom
      || e.utilisateur?.departement?.nom
      || 'Sans département'
    const fil  = e.filiere?.nom || 'Sans filière'
    if (!depts[dept]) depts[dept] = {}
    if (!depts[dept][fil]) depts[dept][fil] = []
    depts[dept][fil].push(e)
  }
  return Object.entries(depts).sort(([a], [b]) => a.localeCompare(b))
    .map(([dept, filieres]) => ({
      dept,
      filieres: Object.entries(filieres).sort(([a], [b]) => a.localeCompare(b)),
      total: Object.values(filieres).flat().length,
    }))
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Étudiants</h1>
        <p class="text-sm text-slate-400">{{ items.length }} étudiants enregistrés</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouvel étudiant
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un étudiant…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-4xl border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-4xl border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400">Aucun étudiant trouvé</div>

    <div v-else class="space-y-6">
      <section v-for="deptGroup in groupedByDeptFiliere" :key="deptGroup.dept">

        <!-- Département header -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-4 py-2">
            <i class="fa-solid fa-building-columns text-[#d6e87a] text-xs"></i>
            <span class="text-xs font-black text-white">{{ deptGroup.dept }}</span>
            <span class="rounded-md bg-[#d6e87a] text-[#1e4a49] px-1.5 py-0.5 text-[10px] font-black">{{ deptGroup.total }}</span>
          </div>
          <div class="flex-1 h-px bg-slate-200"></div>
        </div>

        <!-- Filière sub-groups -->
        <div v-for="([filiere, etudiants]) in deptGroup.filieres" :key="filiere" class="space-y-2 pl-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="flex items-center gap-2 rounded-xl bg-[#1e4a49]/10 px-3 py-1">
              <i class="fa-solid fa-layer-group text-[#1e4a49] text-[10px]"></i>
              <span class="text-[11px] font-bold text-[#1e4a49]">{{ filiere }}</span>
              <span class="rounded bg-[#1e4a49] text-[#d6e87a] px-1 py-0.5 text-[9px] font-black">{{ etudiants.length }}</span>
            </div>
            <div class="flex-1 h-px bg-slate-100"></div>
          </div>

          <div class="rounded-4xl border border-white/70 bg-white/90 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50/70 text-left text-[10px] font-bold uppercase tracking-widest text-slate-400">
                  <th class="px-6 py-3">Étudiant</th>
                  <th class="px-4 py-3">Code</th>
                  <th class="px-4 py-3">Niveau</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="e in etudiants" :key="e.id" class="border-t border-slate-100 hover:bg-slate-50/60 transition">
                  <td class="px-6 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#d6e87a] text-xs font-black text-slate-700">
                        {{ (e.utilisateur?.prenom || 'E')[0] }}{{ (e.utilisateur?.nom || '')[0] }}
                      </div>
                      <div>
                        <p class="font-semibold text-slate-800">{{ e.utilisateur?.prenom }} {{ e.utilisateur?.nom }}</p>
                        <p class="text-xs text-slate-400">{{ e.utilisateur?.courriel }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ e.code_etudiant }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">{{ e.niveau }}</span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button @click="openEdit(e)" class="mr-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button @click="remove(e.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-4xl bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouvel' }} étudiant</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Utilisateur</label>
              <select v-model="form.user_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.prenom }} {{ u.nom }} ({{ u.courriel }})</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Code étudiant</label>
              <input v-model="form.code_etudiant" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Filière</label>
              <select v-model="form.filiere_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="">— Sélectionner —</option>
                <option v-for="f in filieres" :key="f.id" :value="f.id">{{ f.nom }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Niveau</label>
              <select v-model="form.niveau" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]">
                <option value="L1">L1</option>
                <option value="L2">L2</option>
                <option value="L3">L3</option>
                <option value="M1">M1</option>
                <option value="M2">M2</option>
              </select>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
