<script setup lang="ts">
import type { EntityResource } from '@/types'
import type { LayerGroup, Map as LeafletMap, Marker } from 'leaflet'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

interface Props {
  resources: EntityResource[]
  selectedId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  selectedId: null,
})

const emit = defineEmits<{
  (event: 'select', id: string): void
}>()

const mapElement = ref<HTMLElement | null>(null)
let map: LeafletMap | null = null
let markerLayer: LayerGroup | null = null
let leaflet: typeof import('leaflet') | null = null
const markers = new Map<string, Marker>()

const palette = ['#2563eb', '#16a34a', '#ea580c', '#9333ea', '#db2777', '#0891b2', '#4f46e5']

function colorFor(type: string): string {
  let hash = 0

  for (const character of type) {
    hash = character.charCodeAt(0) + ((hash << 5) - hash)
  }

  return palette[Math.abs(hash) % palette.length]
}

function popupFor(resource: EntityResource): HTMLElement {
  const container = document.createElement('div')
  container.className = 'resource-map-popup'

  const type = document.createElement('span')
  type.className = 'resource-map-popup__type'
  type.textContent = resource.type

  const title = document.createElement('strong')
  title.className = 'resource-map-popup__title'
  title.textContent = resource.name

  const address = document.createElement('span')
  address.className = 'resource-map-popup__address'
  address.textContent = resource.address

  container.append(type, title, address)

  return container
}

function renderMarkers(): void {
  if (!map || !leaflet || !markerLayer) return

  markerLayer.clearLayers()
  markers.clear()
  const bounds: [number, number][] = []

  for (const resource of props.resources) {
    const latitude = Number(resource.latitude)
    const longitude = Number(resource.longitude)

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) continue

    const color = colorFor(resource.type)
    const icon = leaflet.divIcon({
      className: 'resource-map-marker-wrapper',
      html: `<span class="resource-map-marker" style="--marker-color:${color}"><span></span></span>`,
      iconSize: [34, 42],
      iconAnchor: [17, 42],
      popupAnchor: [0, -38],
    })
    const marker = leaflet.marker([latitude, longitude], { icon })
      .bindPopup(popupFor(resource))
      .on('click', () => emit('select', resource.id))
      .addTo(markerLayer)

    markers.set(resource.id, marker)
    bounds.push([latitude, longitude])
  }

  if (bounds.length === 1) {
    map.setView(bounds[0], 14)
  } else if (bounds.length > 1) {
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 })
  } else {
    map.setView([40.4168, -3.7038], 6)
  }

  focusSelected(false)
}

function focusSelected(pan = true): void {
  if (!map || !props.selectedId) return

  const marker = markers.get(props.selectedId)
  if (!marker) return

  if (pan) {
    map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 14), { duration: 0.5 })
  }

  marker.openPopup()
}

onMounted(async () => {
  if (!mapElement.value) return

  leaflet = await import('leaflet')
  map = leaflet.map(mapElement.value, {
    zoomControl: true,
    scrollWheelZoom: true,
  })
  markerLayer = leaflet.layerGroup().addTo(map)

  leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map)

  renderMarkers()
  await nextTick()
  map.invalidateSize()
})

watch(() => props.resources, renderMarkers, { deep: true })
watch(() => props.selectedId, () => focusSelected())

onBeforeUnmount(() => {
  map?.remove()
  map = null
  markerLayer = null
  markers.clear()
})
</script>

<template>
  <div ref="mapElement" class="h-full min-h-96 w-full" role="region" :aria-label="$t('resources.map_aria_label')"></div>
</template>

<style>
.resource-map-marker-wrapper {
  background: transparent;
  border: 0;
}

.resource-map-marker {
  position: relative;
  display: block;
  width: 34px;
  height: 34px;
  transform: rotate(45deg);
  border: 3px solid white;
  border-radius: 50% 50% 50% 0;
  background: var(--marker-color);
  box-shadow: 0 3px 10px rgb(15 23 42 / 30%);
}

.resource-map-marker > span {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 10px;
  height: 10px;
  transform: translate(-50%, -50%);
  border-radius: 9999px;
  background: white;
}

.resource-map-popup {
  display: flex;
  min-width: 180px;
  flex-direction: column;
  gap: 4px;
}

.resource-map-popup__type {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.resource-map-popup__title {
  color: #111827;
  font-size: 15px;
}

.resource-map-popup__address {
  color: #637381;
  line-height: 1.4;
}
</style>
