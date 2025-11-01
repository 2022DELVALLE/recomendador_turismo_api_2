# generar_embedding.py

import sys
import json
import os
import numpy as np
import traceback

try:
    from sentence_transformers import SentenceTransformer
    model = SentenceTransformer('all-MiniLM-L6-v2')
except ImportError as e:
    sys.stderr.write(f"Error: Librería 'sentence-transformers' no encontrada: {e}\n")
    sys.exit(1)

def calcular_similitud_coseno(vector_a, vector_b):
    """Calcula la similitud coseno entre dos vectores."""
    dot_product = np.dot(vector_a, vector_b)
    norm_a = np.linalg.norm(vector_a)
    norm_b = np.linalg.norm(vector_b)
    return float(dot_product / (norm_a * norm_b)) if norm_a and norm_b else 0.0

if __name__ == '__main__':
    try:
        # Validar argumentos
        if len(sys.argv) < 2:
            sys.stderr.write("Error: Falta la ruta del archivo JSON de entrada.\n")
            sys.stderr.write(f"Uso: {sys.argv[0]} <ruta_archivo.json>\n")
            sys.exit(1)

        json_file_path = sys.argv[1]

        # Verificar existencia del archivo
        if not os.path.exists(json_file_path):
            sys.stderr.write(f"Error: Archivo no encontrado: {json_file_path}\n")
            sys.exit(1)

        # Leer archivo JSON
        with open(json_file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        # Validar estructura del JSON
        if 'texto' not in data or 'contexto_vector' not in data:
            sys.stderr.write("Error: JSON debe contener 'texto' y 'contexto_vector'.\n")
            sys.exit(1)

        user_text_input = data['texto']
        context_vector_list = data['contexto_vector']

        # Validar que el vector no esté vacío
        if not context_vector_list or len(context_vector_list) == 0:
            sys.stderr.write("Error: Vector de contexto vacío.\n")
            sys.exit(1)

        # Generar embedding del usuario
        user_embedding = model.encode([user_text_input])[0]
        user_embedding_list = user_embedding.tolist()

        # Convertir vector de contexto a numpy array
        context_vector = np.array(context_vector_list, dtype=np.float32)

        # Calcular similitud coseno
        w_uc = calcular_similitud_coseno(user_embedding, context_vector)

        # Preparar resultado
        resultado = {
            'U0_vector': user_embedding_list,
            'WUC_peso': w_uc
        }

        # Imprimir resultado como JSON (stdout)
        print(json.dumps(resultado, ensure_ascii=False))
        sys.exit(0)

    except json.JSONDecodeError as e:
        sys.stderr.write(f"Error decodificando JSON: {e}\n")
        sys.exit(1)
    except FileNotFoundError as e:
        sys.stderr.write(f"Error de archivo: {e}\n")
        sys.exit(1)
    except Exception as e:
        sys.stderr.write(f"Error inesperado: {str(e)}\n")
        sys.stderr.write(traceback.format_exc())
        sys.exit(1)