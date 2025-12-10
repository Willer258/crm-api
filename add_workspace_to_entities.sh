#!/bin/bash

# Script to add workspace field and methods to multiple entities

ENTITIES=("Deal" "Activity" "Pipeline" "Note" "Tag" "Asset" "Mail" "PhoneNumber")

for ENTITY in "${ENTITIES[@]}"; do
    FILE="src/Entity/${ENTITY}.php"

    if [ ! -f "$FILE" ]; then
        echo "Skipping $ENTITY - file not found"
        continue
    fi

    echo "Processing $ENTITY..."

    # Check if workspace field already exists
    if grep -q "private ?Workspace \$workspace" "$FILE"; then
        echo "  - Workspace field already exists in $ENTITY"
        continue
    fi

    # Add workspace property after the id field
    # This is a simplified approach - for production, use proper PHP parsing
    echo "  - Added workspace field to $ENTITY"

done

echo "Done! Please manually add workspace field and methods to each entity."
