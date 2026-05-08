<script setup></script>
<template>

  <div class="flex w-full h-screen bg-white font-sans antialiased selection:bg-[#D4E98D]/30 overflow-hidden">

    <!-- LEFT SECTION (Visual Brand) -->
    <div class="hidden lg:block relative overflow-hidden flex-shrink-0 border-r border-gray-100" style="width:55%">
      <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&q=80&w=2070"
        class="absolute inset-0 w-full h-full object-cover animate-slowzoom" alt="Campus" />
      
      <!-- Sophisticated Overlay -->
      <div class="absolute inset-0 bg-gradient-to-tr from-black/80 via-black/40 to-transparent"></div>
      <div class="absolute inset-0 bg-[#1e4a49]/20 backdrop-blur-[1px]"></div>

      <div class="relative z-10 h-full flex flex-col p-16">
        <div class="flex items-center gap-4 animate-fadein">
          <div class="w-12 h-12 bg-[#D4E98D] rounded-[1.2rem] flex items-center justify-center text-[#1e4a49] text-2xl shadow-lg">
            <i class="fa-solid fa-seedling"></i>
          </div>
          <span class="font-black text-2xl text-white tracking-tighter uppercase">FSBM University</span>
        </div>

        <div class="mt-auto max-w-xl">
          <span class="inline-block px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[#D4E98D] text-[10px] font-black uppercase tracking-[0.2em] mb-6 animate-fadein">Est. 2010</span>
          <h2 class="font-black text-7xl text-white leading-[0.95] mb-8 animate-fadein tracking-tighter">
            Grow your <span class="text-[#D4E98D]">future</span><br />with us.
          </h2>
          <p class="text-white/80 text-lg leading-relaxed animate-fadein-d1 font-medium">
            Join a community of <span class="text-white font-bold underline decoration-[#D4E98D] decoration-2 underline-offset-4">2,635+ students</span> and elite educators in an environment designed for academic excellence.
          </p>
          
          <div class="flex gap-4 mt-12 flex-wrap animate-fadein-d2">
            <div class="glass-pill rounded-2xl px-6 py-3 flex items-center gap-3 text-white text-sm font-bold shadow-xl border border-white/10 transition-transform hover:scale-105 cursor-default">
              <div class="w-2 h-2 rounded-full bg-[#D4E98D] animate-pulse"></div>
              <span>2,635 Students</span>
            </div>
            <div class="glass-pill rounded-2xl px-6 py-3 flex items-center gap-3 text-white text-sm font-bold shadow-xl border border-white/10 transition-transform hover:scale-105 cursor-default">
              <div class="w-2 h-2 rounded-full bg-blue-400"></div>
              <span>29 Teachers</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="flex-1 flex items-center justify-center px-6 lg:px-20 py-10 relative bg-[#fcfdfc]">
        <slot />
    </div>
    
  </div>
</template>