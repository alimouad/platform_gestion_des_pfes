<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoginLayout from '@/layouts/authLayout.vue';
import apiClient, { setAuthToken } from '@/services/api'
const router = useRouter()

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const role = ref('etudiant')
const emailError = ref(false)
const passwordError = ref(false)
const loginError = ref('')

function roleBtnClass(name) {
  return [
    'role-tab flex-1 flex flex-col items-center py-3 rounded-2xl text-[13px] font-bold transition-all duration-300',
    role.value === name 
      ? 'bg-white text-gray-900 shadow-[0_8px_20px_rgba(0,0,0,0.06)] scale-[1.02]' 
      : 'text-gray-400 hover:text-gray-600'
  ]
}

function setRole(name) { role.value = name }
function togglePw() { showPassword.value = !showPassword.value }

async function submit() {
  emailError.value = false
  passwordError.value = false
  loginError.value = ''

  if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailError.value = true
  }
  if (!password.value || password.value.length < 8) {
    passwordError.value = true
  }
  if (emailError.value || passwordError.value) return

  loading.value = true
  try {
    const res = await apiClient.post('/login', {
      courriel: email.value,
      mot_de_passe: password.value,
    })
    if (res.data?.token) {
      setAuthToken(res.data.token)
      const me = await apiClient.get('/me')
      const user = me.data?.data
      localStorage.setItem('admin_user', JSON.stringify(user || {}))
      if (user?.role === 'superadmin') {
        router.push('/admin/dashboard')
      } else if (user?.role === 'coordinateur') {
        router.push('/coordinateur/dashboard')
      } else if (user?.role === 'professeur') {
        router.push('/professeur/dashboard')
      } else if (user?.role === 'etudiant') {
        router.push('/etudiant/dashboard')
      } else {
        router.push('/')
      }
    }
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Identifiants invalides.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
<LoginLayout>

    <!-- RIGHT SECTION (Login Form) -->
    
      <!-- Abstract decorative shapes -->
      <div class="absolute -top-24 -right-24 w-80 h-80 bg-[#D4E98D]/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-[#1e4a49]/5 rounded-full blur-3xl"></div>

      <div class="w-full max-w-md relative z-10 animate-fadein">
        <div class="mb-10">
          <h1 class="font-black text-4xl text-gray-900 tracking-tighter leading-tight mb-3">
            Welcome back
          </h1>
          <p class="text-gray-500 font-medium">Enter your credentials to access your portal.</p>
        </div>

        <!-- Role Switcher -->
        <div class="flex gap-1 bg-gray-100 rounded-3xl p-1.5 mb-10 border border-gray-200/50">
          <button v-for="r in ['etudiant', 'professeur', 'superadmin']" 
                  :key="r"
                  :class="roleBtnClass(r)" 
                  @click="setRole(r)" 
                  type="button">
            <i :class="[
              'mb-1.5 text-lg transition-transform duration-300', 
              r === 'etudiant' ? 'fa-solid fa-user-graduate' : r === 'professeur' ? 'fa-solid fa-chalkboard-user' : 'fa-solid fa-user-shield',
              role === r ? 'scale-110' : 'scale-100'
            ]"></i>
            <span class="capitalize">{{ r === 'superadmin' ? 'Admin' : r === 'etudiant' ? 'Student' : 'Teacher' }}</span>
          </button>
        </div>

        <form @submit.prevent="submit" novalidate class="space-y-6">
          <!-- Email Input -->
          <div class="group">
            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1" for="email">Email Address</label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 transition-colors group-focus-within:text-[#1e4a49]"></i>
              <input v-model="email" id="email" type="email" placeholder="name@oak.edu"
                :class="['w-full bg-white border-2 rounded-[1.25rem] py-4 pl-12 pr-4 text-sm font-bold shadow-sm transition-all outline-none', 
                emailError ? 'border-red-400 bg-red-50/30' : 'border-gray-100 focus:border-[#D4E98D] focus:shadow-md']" />
            </div>
            <Transition name="slide-fade">
              <p v-if="emailError" class="text-[11px] font-bold text-red-500 mt-2 px-1">Please provide a valid institutional email.</p>
            </Transition>
          </div>

          <!-- Password Input -->
          <div class="group">
            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1" for="password">Password</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 transition-colors group-focus-within:text-[#1e4a49]"></i>
              <input v-model="password" :type="showPassword ? 'text' : 'password'" id="password" placeholder="••••••••"
                :class="['w-full bg-white border-2 rounded-[1.25rem] py-4 pl-12 pr-12 text-sm font-bold shadow-sm transition-all outline-none', 
                passwordError ? 'border-red-400 bg-red-50/30' : 'border-gray-100 focus:border-[#D4E98D] focus:shadow-md']" />
              <button type="button" @click="togglePw" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-900 transition-colors">
                <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
              </button>
            </div>
            <Transition name="slide-fade">
              <p v-if="passwordError" class="text-[11px] font-bold text-red-500 mt-2 px-1">Password must be at least 8 characters long.</p>
            </Transition>
          </div>

          <div class="flex justify-between items-center px-1">
            <label class="flex items-center gap-3 cursor-pointer group">
              <div class="relative w-5 h-5">
                <input type="checkbox" v-model="remember" class="peer absolute inset-0 opacity-0 cursor-pointer z-10" />
                <div class="w-5 h-5 border-2 border-gray-200 rounded-md transition-all peer-checked:bg-[#D4E98D] peer-checked:border-[#D4E98D] group-hover:border-[#D4E98D]"></div>
                <i class="fa-solid fa-check absolute top-1 left-1 text-[10px] text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
              </div>
              <span class="text-sm font-bold text-gray-500 group-hover:text-gray-900 transition-colors">Stay signed in</span>
            </label>
            <router-link to="/forgot" class="text-sm font-black text-[#1e4a49] hover:underline decoration-[#D4E98D] decoration-2 underline-offset-4 transition-all">Forgot secret?</router-link>
          </div>

          <Transition name="slide-fade">
            <p v-if="loginError" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm font-bold text-red-500 text-center">{{ loginError }}</p>
          </Transition>

          <button :disabled="loading" type="submit" 
            class="group relative w-full bg-gray-900 text-white rounded-[1.25rem] py-4.5 font-black text-sm tracking-widest uppercase overflow-hidden shadow-2xl transition-all hover:-translate-y-1 hover:shadow-[#D4E98D]/20 active:translate-y-0 disabled:opacity-70 disabled:cursor-not-allowed">
            <span :class="{ 'opacity-0': loading }" class="transition-opacity">{{ loading ? 'Authenticating...' : 'Secure Login' }}</span>
            
            <div class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-[#D4E98D] rounded-xl flex items-center justify-center text-[#1e4a49] shadow-inner">
              <i v-if="!loading" class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
              <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
            </div>
          </button>
        </form>

        <div class="relative my-10">
          <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
          <div class="relative flex justify-center text-[10px] font-black uppercase tracking-[0.3em]"><span class="bg-[#fcfdfc] px-4 text-gray-400">Institutional SSO</span></div>
        </div>

        <button class="w-full flex items-center justify-center gap-4 bg-white border-2 border-gray-100 rounded-[1.25rem] py-4 text-sm font-black text-gray-800 transition-all hover:border-[#D4E98D] hover:shadow-lg active:scale-[0.98]">
          <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.5 30.2 0 24 0 14.7 0 6.7 5.4 2.9 13.2l7.8 6.1C12.5 13 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4 7.1-10 7.1-17z"/><path fill="#FBBC05" d="M10.7 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.8-6.1A24 24 0 0 0 0 24c0 3.8.9 7.4 2.5 10.6l8.2-5.9z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.6 2.2-7.7 2.2-6.2 0-11.5-4.2-13.3-9.8l-8.2 5.9C6.6 42.5 14.7 48 24 48z"/></svg>
          Google Workspace
        </button>

        
      </div>
    </LoginLayout>
</template>

<style scoped>
/* Advanced Animations */
@keyframes slowzoom {
  0% { transform: scale(1); }
  100% { transform: scale(1.1); }
}
.animate-slowzoom { animation: slowzoom 30s ease-in-out infinite alternate; }

@keyframes fadein {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadein { animation: fadein 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) both; }
.animate-fadein-d1 { animation-delay: 0.2s; }
.animate-fadein-d2 { animation-delay: 0.4s; }

/* Transitions */
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateX(10px); opacity: 0; }

.glass-pill {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
}

/* Custom UI Tweaks */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px white inset;
  transition: background-color 5000s ease-in-out 0s;
}

button:disabled {
  transform: none !important;
  box-shadow: none !important;
}

.py-4\.5 { padding-top: 1.125rem; padding-bottom: 1.125rem; }
</style>