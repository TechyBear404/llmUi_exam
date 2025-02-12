<template>
    <div class="w-full">
        <Select
            :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <SelectTrigger class="w-full">
                <SelectValue :placeholder="placeholder">
                    {{ selectedModelName }}
                </SelectValue>
            </SelectTrigger>
            <SelectContent>
                <SelectLabel>AI Models</SelectLabel>
                <SelectGroup>
                    <SelectItem
                        v-for="model in models"
                        :key="model.id"
                        :value="model.id"
                    >
                        <div class="flex items-center">
                            <font-awesome-icon
                                :icon="['fas', 'robot']"
                                class="mr-2 h-4 w-4 text-muted-foreground"
                            />
                            <span>{{ model.name }}</span>
                        </div>
                    </SelectItem>
                </SelectGroup>
            </SelectContent>
        </Select>
    </div>
</template>

<script setup>
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { computed } from "vue";

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        required: true,
    },
    placeholder: {
        type: String,
        default: "Select an AI model",
    },
});

const selectedModelName = computed(() => {
    const selectedModel = props.models.find(
        (model) => model.id === props.modelValue
    );
    return selectedModel ? selectedModel.name : props.placeholder;
});

defineEmits(["update:modelValue"]);
</script>

<style lang="scss" scoped></style>
