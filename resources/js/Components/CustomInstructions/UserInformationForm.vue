<template>
    <div class="space-y-6">
        <h3 class="text-lg font-medium text-primary-foreground">
            Informations Utilisateur
        </h3>

        <div class="space-y-2">
            <label class="text-sm font-medium text-muted-foreground"
                >Contexte</label
            >
            <Textarea
                v-model="form.user_background"
                rows="3"
                placeholder="Décrivez votre parcours..."
                class="w-full resize-none bg-muted/50"
            />
            <div
                v-if="form.errors.user_background"
                class="text-sm text-destructive"
            >
                {{ form.errors.user_background }}
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-muted-foreground"
                >Centres d'Intérêt</label
            >
            <div class="space-y-2">
                <Input
                    v-model="newInterest"
                    @keydown.enter.prevent="addInterest"
                    placeholder="Tapez et appuyez sur Entrée pour ajouter"
                    class="bg-muted/50"
                />
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="(interest, index) in form.user_interests"
                        :key="index"
                        variant="secondary"
                        class="transition-colors cursor-pointer hover:bg-destructive hover:text-destructive-foreground"
                        @click="removeInterest(index)"
                    >
                        {{ interest }}
                        <font-awesome-icon
                            icon="fa-solid fa-times"
                            class="ml-2"
                        />
                    </Badge>
                </div>
            </div>
            <div
                v-if="form.errors.user_interests"
                class="text-sm text-destructive"
            >
                {{ form.errors.user_interests }}
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-muted-foreground"
                >Niveaux de Connaissance</label
            >
            <div class="space-y-2">
                <div
                    v-for="(level, index) in form.knowledge_levels"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <Input
                        v-model="level.subject"
                        placeholder="Sujet"
                        class="flex-1 bg-muted/50"
                    />
                    <Select v-model="level.level" class="">
                        <SelectTrigger class="w-48 bg-muted/50">
                            <SelectValue placeholder="Niveau" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="beginner">Débutant</SelectItem>
                            <SelectItem value="intermediate"
                                >Intermédiaire</SelectItem
                            >
                            <SelectItem value="advanced">Avancé</SelectItem>
                            <SelectItem value="expert">Expert</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button
                        @click="removeKnowledgeLevel(index)"
                        variant="destructive"
                        size="icon"
                    >
                        <font-awesome-icon icon="fa-solid fa-trash" />
                    </Button>
                </div>
                <Button
                    type="button"
                    @click="addKnowledgeLevel"
                    variant="outline"
                    size="sm"
                    class="w-full bg-muted/50"
                >
                    <font-awesome-icon icon="fa-solid fa-plus" class="mr-2" />
                    Ajouter un Niveau
                </Button>
            </div>
            <div
                v-if="form.errors.knowledge_levels"
                class="text-sm text-destructive"
            >
                {{ form.errors.knowledge_levels }}
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-muted-foreground"
                >Objectifs</label
            >
            <Textarea
                v-model="form.user_goals"
                rows="3"
                placeholder="Décrivez vos objectifs..."
                class="w-full resize-none bg-muted/50"
            />
            <div v-if="form.errors.user_goals" class="text-sm text-destructive">
                {{ form.errors.user_goals }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { Badge } from "@/Components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const newInterest = ref("");

const addInterest = () => {
    if (newInterest.value.trim()) {
        props.form.user_interests.push(newInterest.value.trim());
        newInterest.value = "";
    }
};

const removeInterest = (index) => {
    props.form.user_interests.splice(index, 1);
};

const addKnowledgeLevel = () => {
    props.form.knowledge_levels.push({
        subject: "",
        level: "beginner",
    });
};

const removeKnowledgeLevel = (index) => {
    props.form.knowledge_levels.splice(index, 1);
};
</script>
