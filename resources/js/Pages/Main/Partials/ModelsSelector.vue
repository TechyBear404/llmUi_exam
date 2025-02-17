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
                                        <div
                                            class="flex items-center gap-2 text-xs text-gray-500"
                                        >
                                            <span>{{
                                                model.id.includes(":free")
                                                    ? "Gratuit"
                                                    : "Payant"
                                            }}</span>
                                            <span
                                                v-if="model.supports_image"
                                                class="flex items-center gap-1"
                                            >
                                                •
                                                <font-awesome-icon
                                                    icon="fa-solid fa-image"
                                                    class="w-3 h-3"
                                                />
                                                Image
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Gratuits</span>
                <Switch
                    :checked="isFreeModelsOnly"
                    @update:checked="toggleFreeModels"
                />
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Images</span>
                <Switch
                    :checked="isImageModelsOnly"
                    @update:checked="toggleImageModels"
                />
            </div>
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
const isImageModelsOnly = ref(false);

const toggleFreeModels = (value) => {
    isFreeModelsOnly.value = value;
};

const toggleImageModels = (value) => {
    isImageModelsOnly.value = value;
};

const filteredModels = computed(() => {
    let filtered = props.models;

    if (isFreeModelsOnly.value) {
        filtered = filtered.filter((model) =>
            model.id.toLowerCase().includes(":free")
        );
    }

    if (isImageModelsOnly.value) {
        filtered = filtered.filter((model) => model.supports_image);
    }

    return filtered;
});

watch(
    [isFreeModelsOnly, isImageModelsOnly],
    ([newFreeValue, newImageValue]) => {
        const currentModel = props.models.find(
            (model) => model.id === props.modelValue
        );
        if (!currentModel) return;

        const shouldChange =
            (newFreeValue &&
                !currentModel.id.toLowerCase().includes(":free")) ||
            (newImageValue && !currentModel.supports_image);

        if (shouldChange && filteredModels.value.length > 0) {
            emit("update:modelValue", filteredModels.value[0].id);
        }
    }
);

const selectedModelName = computed(() => {
    const selectedModel = props.models.find(
        (model) => model.id === props.modelValue
    );
    // log models ordered by context_length
    console.log(
        "models ordered by context_length",
        props.models.sort((a, b) => a.context_length - b.context_length)
    );
    return selectedModel ? selectedModel.name : props.placeholder;
});
</script>

<style lang="scss" scoped></style>
