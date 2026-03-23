<script setup>
import { onMounted, onUnmounted, ref, nextTick } from 'vue'

const props = defineProps({
  target: { type: Object, required: true },
  duration: { type: Number, default: 3000 },
})

const canvas = ref(null)

let fireworkInterval = null
let rafId = null
let running = true

onMounted(async () => {
  await nextTick()

  const rect = props.target.getBoundingClientRect()
  const ctx = canvas.value.getContext('2d')
  const dpr = window.devicePixelRatio || 1

  const padding = 14

  canvas.value.width = (rect.width + padding * 2) * dpr
  canvas.value.height = (rect.height + padding * 2) * dpr
  canvas.value.style.width = `${rect.width + padding * 2}px`
  canvas.value.style.height = `${rect.height + padding * 2}px`

  ctx.scale(dpr, dpr)
  ctx.translate(padding, padding)
  ctx.globalCompositeOperation = 'lighter'

  const particles = []
  const colors = ['#fbbf24', '#f472b6', '#a78bfa', '#38bdf8']

  function firework() {
    if (!running) return

    const count = 20 + Math.random() * 15
    const x = rect.width * (0.3 + Math.random() * 0.4)
    const y = rect.height * 0.6

    for (let i = 0; i < count; i++) {
      const angle = Math.random() * Math.PI * 2
      const speed = Math.random() * 2.5 + 1

      particles.push({
        x,
        y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        life: 0,
        ttl: 60 + Math.random() * 30,
        color: colors[Math.floor(Math.random() * colors.length)],
      })
    }
  }

  function animate() {
    ctx.clearRect(-padding, -padding, canvas.value.width, canvas.value.height)

    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i]
      p.life++
      p.x += p.vx
      p.y += p.vy
      p.vy += 0.025

      ctx.globalAlpha = (1 - p.life / p.ttl) * 0.8
      ctx.beginPath()
      ctx.arc(p.x, p.y, 2.2, 0, Math.PI * 2)
      ctx.fillStyle = p.color
      ctx.shadowBlur = 4
      ctx.shadowColor = p.color
      ctx.fill()

      if (p.life > p.ttl) particles.splice(i, 1)
    }

    ctx.globalAlpha = 1

    if (running || particles.length > 0) {
      rafId = requestAnimationFrame(animate)
    }
  }

  fireworkInterval = setInterval(firework, 300)

  setTimeout(() => {
    running = false
    clearInterval(fireworkInterval)
  }, props.duration)

  animate()
})

onUnmounted(() => {
  running = false

  if (fireworkInterval) {
    clearInterval(fireworkInterval)
    fireworkInterval = null
  }

  if (rafId) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
})
</script>

<template>
  <canvas ref="canvas" class="pointer-events-none absolute inset-0 z-10" />
</template>