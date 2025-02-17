<template>
    <div class="flex items-center w-full space-x-4">
        <div class="flex-1">
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
                    <SelectLabel>Modèles d'IA</SelectLabel>
                    <SelectGroup>
                        <SelectItem
                            v-for="model in filteredModels"
                            :key="model.id"
                            :value="model.id"
                        >
                            <div
                                class="flex items-center justify-between w-full"
                            >
                                <div class="flex items-center">
                                    <font-awesome-icon
                                        :icon="['fas', 'robot']"
                                        class="w-4 h-4 mr-2 text-muted-foreground"
                                    />
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{
                                            model.name
                                        }}</span>
                                        <span class="text-xs text-gray-500">
                                            {{
                                                model.id.includes(":free")
                                                    ? "Gratuit"
                                                    : "Payant"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Modèles gratuits</span>
            <Switch
                :checked="isFreeModelsOnly"
                @update:checked="toggleFreeModels"
            />
        </div>
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
} from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { computed, ref, watch } from "vue";

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
        default: "Sélectionnez un modèle d'IA",
    },
});

const emit = defineEmits(["update:modelValue"]);
const isFreeModelsOnly = ref(false);

const toggleFreeModels = (value) => {
    isFreeModelsOnly.value = value;
};

const filteredModels = computed(() => {
    if (isFreeModelsOnly.value) {
        return props.models.filter((model) =>
            model.id.toLowerCase().includes(":free")
        );
    }
    return props.models;
});

watch(isFreeModelsOnly, (newValue) => {
    if (newValue) {
        const currentModel = props.models.find(
            (model) => model.id === props.modelValue
        );
        if (
            !currentModel?.id.toLowerCase().includes(":free") &&
            filteredModels.value.length > 0
        ) {
            emit("update:modelValue", filteredModels.value[0].id);
        }
    }
});

const selectedModelName = computed(() => {
    const selectedModel = props.models.find(
        (model) => model.id === props.modelValue
    );
    return selectedModel ? selectedModel.name : props.placeholder;
});
</script>

<style lang="scss" scoped></style>
