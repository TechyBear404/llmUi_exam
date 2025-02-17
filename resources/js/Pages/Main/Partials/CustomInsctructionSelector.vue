<template>
    <div class="w-full">
        <Select
            :model-value="modelValueAsString"
            @update:model-value="handleValueUpdate"
        >
            <SelectTrigger class="w-[250px]">
                <SelectValue :placeholder="placeholder">
                    {{ selectedInstructionName }}
                </SelectValue>
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectItem value="none">
                        <div class="flex items-center">
                            <font-awesome-icon
                                :icon="['fas', 'scroll']"
                                class="w-4 h-4 mr-2 text-muted-foreground"
                            />
                            <span>Pas d'instruction</span>
                        </div>
                    </SelectItem>

                    <SelectItem
                        v-for="instruction in instructions"
                        :key="instruction.id"
                        :value="String(instruction.id)"
                    >
                        <div class="flex items-center">
                            <font-awesome-icon
                                :icon="['fas', 'scroll']"
                                class="w-4 h-4 mr-2 text-muted-foreground"
                            />
                            <span>{{ instruction.title }}</span>
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
} from "@/Components/ui/select";
import { computed } from "vue";

const props = defineProps({
    instructions: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        required: false,
    },
    placeholder: {
        type: String,
        default: "Sélectionnez une instruction",
    },
});

const modelValueAsString = computed(() =>
    props.modelValue === null ? "none" : String(props.modelValue)
);

const emit = defineEmits(["update:modelValue"]);

const handleValueUpdate = (value) => {
    emit("update:modelValue", value === "none" ? null : Number(value));
};

const selectedInstructionName = computed(() => {
    if (props.modelValue === null) {
        return props.placeholder;
    }
    const selectedInstruction = props.instructions.find(
        (instruction) => instruction.id === Number(props.modelValue)
    );
    return selectedInstruction ? selectedInstruction.title : props.placeholder;
});
</script>
