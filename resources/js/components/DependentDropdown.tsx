import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface DropdownField {
    name: string;
    label: string;
    options?: { value: string; label: string }[];
    dependencies?: Record<string, { value: string; label: string }[]>;
    apiEndpoint?: string;
    disabled?: boolean;
    selectFirstOption?: boolean;
}

interface DependentDropdownProps {
    fields: DropdownField[];
    values: Record<string, string>;
    onChange: (fieldName: string, value: string, formData?: any, additionalData?: any) => void;
    disabled?: boolean;
}

export default function DependentDropdown({ fields, values, onChange, disabled = false }: DependentDropdownProps) {
    const { base_url } = usePage().props as any

    const [availableOptions, setAvailableOptions] = useState<Record<string, { value: string; label: string }[]>>(() => {
        const initial: Record<string, { value: string; label: string }[]> = {};
        fields.forEach((field, index) => {
            if (index === 0) {
                initial[field.name] = field.options || [];
            } else {
                initial[field.name] = [];
            }
        });
        return initial;
    });
    const [loading, setLoading] = useState<Record<string, boolean>>({});

    // Load options from API
    const loadOptionsFromAPI = async (field: DropdownField, parentValue?: string) => {
        if (!field.apiEndpoint) return [];

        setLoading((prev) => ({ ...prev, [field.name]: true }));

        try {
            let endpoint = field.apiEndpoint;
            if (parentValue) {
                // Replace placeholder with actual value - sanitize parentValue
                const sanitizedValue = encodeURIComponent(String(parentValue));
                const parentFieldName = fields[fields.indexOf(field) - 1]?.name;
                if (parentFieldName) {
                    endpoint = endpoint.replace(`{${parentFieldName}}`, sanitizedValue);
                }
            }

            // Loading options from API endpoint
            const response = await fetch(`${base_url}${endpoint}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            // Transform API response to options format
            const options = Array.isArray(data)
                ? data.map((item) => ({
                      value: String(item.id || item.value || ''),
                      label: String(item.name || item.label || 'Unknown'),
                  }))
                : [];

            return options;
        } catch (error) {
            // Silent error handling - don't log user input
            return [];
        } finally {
            setLoading((prev) => ({ ...prev, [field.name]: false }));
        }
    };

    // Initialize available options for dependent fields
    useEffect(() => {
        const loadAllOptions = async () => {
            const newAvailableOptions: Record<string, { value: string; label: string }[]> = {};

            for (let index = 0; index < fields.length; index++) {
                const field = fields[index];

                if (index === 0) {
                    // First field - load from API or use static options
                    if (field.apiEndpoint) {
                        newAvailableOptions[field.name] = await loadOptionsFromAPI(field);
                    } else {
                        newAvailableOptions[field.name] = field.options || [];
                    }
                } else {
                    // Dependent fields need parent value
                    const parentField = fields[index - 1];
                    const parentValue = values[parentField.name];

                    if (parentValue) {
                        if (field.apiEndpoint) {
                            newAvailableOptions[field.name] = await loadOptionsFromAPI(field, parentValue);
                        } else if (field.dependencies) {
                            newAvailableOptions[field.name] = field.dependencies[parentValue] || [];
                        } else {
                            newAvailableOptions[field.name] = [];
                        }
                    } else {
                        newAvailableOptions[field.name] = [];
                    }
                }
            }

            setAvailableOptions(newAvailableOptions);
        };

        loadAllOptions();
    }, [fields]);

    const handleFieldChange = async (fieldName: string, value: string, fieldIndex: number) => {
        // Clear all dependent fields when parent changes
        fields.slice(fieldIndex + 1).forEach((dependentField) => {
            onChange(dependentField.name, '');
        });

        // Get selected field info for the callback
        const currentField = fields.find((f) => f.name === fieldName);
        const currentFieldOptions = availableOptions[fieldName] || currentField?.options || [];
        const selectedItem = currentFieldOptions.find((opt) => String(opt.value) === String(value));
        const selectedInfo = selectedItem ? { id: value, name: selectedItem.label } : null;
        
        // Load options for the next dependent field immediately
        const nextField = fields[fieldIndex + 1];
        if (nextField && value) {
            if (nextField.apiEndpoint) {
                const options = await loadOptionsFromAPI(nextField, value);
                setAvailableOptions((prev) => ({
                    ...prev,
                    [nextField.name]: options,
                }));

                // Auto-select first option if configured
                if (nextField.selectFirstOption && options.length > 0) {
                    setTimeout(() => {
                        onChange(nextField.name, options[0].value);
                    }, 100);
                }

                // Pass selected info with loaded options
                onChange(fieldName, value, { selectedInfo, loadedOptions: options });
                return;
            } else if (nextField.dependencies) {
                const dependentOptions = nextField.dependencies[value] || [];
                setAvailableOptions((prev) => ({
                    ...prev,
                    [nextField.name]: dependentOptions,
                }));

                // Pass selected info with dependent options
                onChange(fieldName, value, { selectedInfo, loadedOptions: dependentOptions });
                return;
            }
        }

        // Update the field value with selected info (for fields without dependents)
        onChange(fieldName, value, { selectedInfo, loadedOptions: [] });
    };

    const getGridCols = () => {
        const count = fields.length;
        if (count <= 2) return 'grid-cols-2';
        if (count <= 3) return 'grid-cols-3';
        if (count <= 4) return 'grid-cols-4';
        return 'grid-cols-5';
    };

    return (
        <div className="space-y-4">
            {fields.map((field, index) => {
                const isFirstField = index === 0;
                const parentField = isFirstField ? null : fields[index - 1];
                const isDisabled = disabled || (!isFirstField && !values[parentField!.name]);
                const fieldOptions = availableOptions[field.name] || [];
                const isLoading = loading[field.name];
                
                return (
                    <div key={field.name} className="space-y-2">
                        <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">{field.label}</label>
                        <select
                            value={values[field.name] || ''}
                            onChange={(e) => handleFieldChange(field.name, e.target.value, index)}
                            disabled={isDisabled || isLoading || field.disabled}
                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option 
                                value=""
                                className="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none text-muted-foreground"
                            >
                                {isLoading ? 'Loading...' : `Select ${field.label.toLowerCase()}`}
                            </option>
                            {fieldOptions.map((option) => (
                                <option 
                                    key={option.value} 
                                    value={option.value}
                                    className="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                                >
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                );
            })}
        </div>
    );
}
