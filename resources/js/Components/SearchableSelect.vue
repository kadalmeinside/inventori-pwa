<template>
  <div class="searchable-select" ref="container">
    <div
      class="ss-input-wrap"
      @click="toggleDropdown"
      :class="{ 'ss-input-wrap--active': isOpen, 'ss-input-wrap--disabled': disabled }"
    >
      <input
        type="text"
        v-model="searchQuery"
        class="ss-input"
        :placeholder="selectedLabel || placeholder"
        :disabled="disabled"
        @focus="openDropdown"
        @input="onInput"
        @keydown.down.prevent="navigateOptions(1)"
        @keydown.up.prevent="navigateOptions(-1)"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.esc="closeDropdown"
      />
      <div class="ss-chevron">
        <svg v-if="!isOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="18 15 12 9 6 15"/></svg>
      </div>
    </div>

    <Transition name="dropdown">
      <div v-if="isOpen" class="ss-dropdown">
        <ul v-if="filteredOptions.length > 0" class="ss-list">
          <li
            v-for="(option, index) in filteredOptions"
            :key="option.value"
            class="ss-option"
            :class="{ 'ss-option--selected': option.value === modelValue, 'ss-option--highlighted': index === highlightedIndex }"
            @click.stop="selectOption(option)"
            @mouseenter="highlightedIndex = index"
          >
            {{ option.label }}
          </li>
        </ul>
        <div v-else class="ss-empty">No results found</div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true }, // Array of { value: '', label: '' }
  placeholder: { type: String, default: 'Select option…' },
  disabled: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const container = ref(null);

// Find label for the current selected value
const selectedLabel = computed(() => {
  const selected = props.options.find(opt => opt.value === props.modelValue);
  return selected ? selected.label : '';
});

// Filter options based on search
const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  const q = searchQuery.value.toLowerCase();
  return props.options.filter(opt => opt.label.toLowerCase().includes(q));
});

watch(isOpen, (newVal) => {
  if (!newVal) {
    // Reset search when closed
    searchQuery.value = '';
    highlightedIndex.value = -1;
  }
});

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
};

const openDropdown = () => {
  if (props.disabled) return;
  isOpen.value = true;
};

const closeDropdown = () => {
  isOpen.value = false;
};

const onInput = () => {
  isOpen.value = true;
  highlightedIndex.value = 0;
};

const selectOption = (option) => {
  emit('update:modelValue', option.value);
  closeDropdown();
};

const navigateOptions = (dir) => {
  if (!isOpen.value) {
    isOpen.value = true;
    return;
  }
  const max = filteredOptions.value.length - 1;
  let newIdx = highlightedIndex.value + dir;
  if (newIdx < 0) newIdx = 0;
  if (newIdx > max) newIdx = max;
  highlightedIndex.value = newIdx;
};

const selectHighlighted = () => {
  if (isOpen.value && highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
    selectOption(filteredOptions.value[highlightedIndex.value]);
  }
};

const handleClickOutside = (e) => {
  if (container.value && !container.value.contains(e.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.searchable-select {
  position: relative;
  width: 100%;
}

.ss-input-wrap {
  position: relative;
  width: 100%;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0.75rem;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
  display: flex;
  align-items: center;
  cursor: text;
}

.ss-input-wrap--active {
  background: #ffffff;
  border-color: rgba(0, 122, 255, 0.4);
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.12);
}

.ss-input-wrap--disabled {
  background: rgba(0, 0, 0, 0.03);
  cursor: not-allowed;
  opacity: 0.7;
}

.ss-input {
  width: 100%;
  border: none;
  background: transparent;
  padding: 0.625rem 2.25rem 0.625rem 0.875rem;
  font-family: inherit;
  font-size: 0.875rem;
  color: rgba(0,0,0,0.85);
  outline: none;
}

.ss-input::placeholder {
  color: rgba(0,0,0,0.85); /* Show selected item as placeholder */
}

/* If no item selected, placeholder should be lighter */
.ss-input:not(:focus)::-webkit-input-placeholder {
  color: rgba(0,0,0,0.45);
}

.ss-chevron {
  position: absolute;
  right: 0.875rem;
  pointer-events: none;
  color: rgba(0,0,0,0.3);
}

.ss-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  width: 100%;
  max-height: 200px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0.75rem;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
  z-index: 50;
  overflow-y: auto;
}

.ss-list {
  list-style: none;
  padding: 0.35rem;
  margin: 0;
}

.ss-option {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  color: rgba(0,0,0,0.75);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.1s;
}

.ss-option:hover, .ss-option--highlighted {
  background: rgba(0, 122, 255, 0.08);
  color: #007AFF;
}

.ss-option--selected {
  background: rgba(0, 122, 255, 0.12);
  color: #007AFF;
  font-weight: 600;
}

.ss-empty {
  padding: 1rem;
  text-align: center;
  font-size: 0.8125rem;
  color: rgba(0,0,0,0.4);
}

/* Animation */
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s, transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1); }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
