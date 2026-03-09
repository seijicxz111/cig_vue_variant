<template>
  <Teleport to="body">
    <div v-if="modelValue" class="preview-modal-backdrop" @click.self="$emit('update:modelValue', false)">
      <div class="preview-modal-box">
        <!-- Header -->
        <div class="preview-modal-header">
          <div class="title-area">
            <i :class="['fas', fileIcon]" style="font-size:1.1rem;flex-shrink:0;"></i>
            <span class="title-text">{{ title }}</span>
            <slot name="badge" />
          </div>
          <slot name="actions" />
          <button class="preview-modal-close" @click="$emit('update:modelValue', false)">&times;</button>
        </div>

        <!-- Body -->
        <div class="preview-body">
          <!-- Loading -->
          <div v-if="state === 'loading'" class="preview-loading">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#047857;"></i>
            <span>{{ loadingMsg }}</span>
          </div>

          <!-- Error -->
          <div v-else-if="state === 'error'" class="preview-error">
            <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
            <span>{{ errorMsg }}</span>
          </div>

          <!-- PDF iframe -->
          <iframe v-show="state === 'pdf'"
            :src="iframeSrc"
            class="preview-iframe"
            @load="onIframeLoad"
            @error="state = 'error'; errorMsg = 'Failed to load PDF.'"
          />

          <!-- DOCX rendered -->
          <div v-if="state === 'docx'" class="preview-docx-wrap" v-html="docxHtml" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  submissionId: [Number, String],
  ext: String,
  title: String,
})
defineEmits(['update:modelValue'])

const state      = ref('loading')
const loadingMsg = ref('Loading document…')
const errorMsg   = ref('')
const iframeSrc  = ref('')
const docxHtml   = ref('')

const fileIcon = computed(() => ({
  pdf:  'fa-file-pdf',
  docx: 'fa-file-word',
  doc:  'fa-file-word',
  xlsx: 'fa-file-excel',
  xls:  'fa-file-excel',
}[props.ext] || 'fa-file-alt'))

watch(() => props.modelValue, (open) => {
  if (!open) { iframeSrc.value = ''; docxHtml.value = ''; return }
  state.value      = 'loading'
  loadingMsg.value = 'Loading document…'
  const { ext, submissionId } = props
  if (ext === 'pdf') {
    iframeSrc.value = `/pages/file_preview.php?submission_id=${submissionId}`
  } else if (ext === 'docx' || ext === 'doc') {
    loadingMsg.value = 'Converting document…'
    iframeSrc.value = `/pages/docx_to_pdf.php?submission_id=${submissionId}`
  } else {
    state.value    = 'error'
    errorMsg.value = `Preview not supported for .${ext} files.`
  }
})

function onIframeLoad() {
  state.value = 'pdf'
}
</script>
