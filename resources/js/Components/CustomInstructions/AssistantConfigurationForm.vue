<template>
    <div class="space-y-6">
        <h3 class="text-lg font-medium text-primary-foreground">
            Configuration de l'Assistant
        </h3>

        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground"
                    >Contexte de l'Assistant</label
                >
                <Textarea
                    v-model="form.assistant_background"
                    placeholder="Description du contexte de l'assistant"
                    class="w-full resize-none bg-muted/50"
                    rows="3"
                />
                <div
                    v-if="form.errors.assistant_background"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.assistant_background }}
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground"
                    >Ton de l'Assistant</label
                >
                <Select v-model="form.assistant_tone">
                    <SelectTrigger class="bg-muted/50">
                        <SelectValue placeholder="Sélectionner le ton" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="friendly">Amical</SelectItem>
                        <SelectItem value="professional"
                            >Professionnel</SelectItem
                        >
                        <SelectItem value="casual">Décontracté</SelectItem>
                        <SelectItem value="formal">Formel</SelectItem>
                        <SelectItem value="technical">Technique</SelectItem>
                        <SelectItem value="educational">Éducatif</SelectItem>
                    </SelectContent>
                </Select>
                <div
                    v-if="form.errors.assistant_tone"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.assistant_tone }}
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground"
                    >Style de Réponse</label
                >
                <Select v-model="form.response_style">
                    <SelectTrigger class="bg-muted/50">
                        <SelectValue placeholder="Sélectionner le style" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="normal">Normal</SelectItem>
                        <SelectItem value="concise">Concis</SelectItem>
                        <SelectItem value="detailed">Détaillé</SelectItem>
                        <SelectItem value="formal">Formel</SelectItem>
                        <SelectItem value="casual">Décontracté</SelectItem>
                    </SelectContent>
                </Select>
                <div
                    v-if="form.errors.response_style"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.response_style }}
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground"
                    >Format de Réponse</label
                >
                <Select v-model="form.response_format">
                    <SelectTrigger class="bg-muted/50">
                        <SelectValue placeholder="Sélectionner le format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="paragraphs">Paragraphes</SelectItem>
                        <SelectItem value="bullet_points">Points</SelectItem>
                        <SelectItem value="step_by_step"
                            >Étape par Étape</SelectItem
                        >
                        <SelectItem value="mixed">Mixte</SelectItem>
                    </SelectContent>
                </Select>
                <div
                    v-if="form.errors.response_format"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.response_format }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from "vue";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>
