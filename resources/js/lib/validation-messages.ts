export const formValidationMessages = {
    required: "Ce champ est obligatoire",
    email: "Veuillez entrer une adresse email valide",
    minLength: (min: number) => `Doit contenir au moins ${min} caractères`,
    maxLength: (max: number) => `Ne doit pas dépasser ${max} caractères`,
    pattern: "La valeur entrée n'est pas valide",
    array: {
        min: (min: number) => `Doit contenir au moins ${min} élément(s)`,
        max: (max: number) => `Ne doit pas dépasser ${max} élément(s)`,
    },
    custom: {
        match: "Les valeurs ne correspondent pas",
        passwordRequirements:
            "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre",
    },
};
