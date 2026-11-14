<script setup lang="ts">
import type { ListboxFilterProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { Search } from "@lucide/vue"
import { ListboxFilter, useForwardPropsEmits } from "reka-ui"
import { cn } from "@/lib/utils"

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<ListboxFilterProps & { class?: HTMLAttributes["class"] }>()
const emits = defineEmits<{
  "update:modelValue": [value: string]
}>()

const forwarded = useForwardPropsEmits(reactiveOmit(props, "class"), emits)
</script>

<template>
  <div class="flex items-center gap-2 border-b px-3" data-slot="command-input-wrapper">
    <Search class="size-4 shrink-0 text-muted-foreground" />
    <ListboxFilter
      data-slot="command-input"
      v-bind="{ ...forwarded, ...$attrs }"
      auto-focus
      :class="cn('placeholder:text-muted-foreground flex h-11 w-full bg-transparent py-3 text-sm outline-none', props.class)"
    />
  </div>
</template>
