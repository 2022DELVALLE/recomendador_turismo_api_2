# generar_embedding.py

import sys
import json
import numpy as np

try:
    from sentence_transformers import SentenceTransformer
    model = SentenceTransformer('all-MiniLM-L6-v2') 
except ImportError:
    sys.stderr.write("Error: Librería 'sentence-transformers' no encontrada. ¿Está activo el entorno 'tarma_ai'?\n")
    sys.exit(1)

def calcular_similitud_coseno(vector_a, vector_b):
    dot_product = np.dot(vector_a, vector_b)
    norm_a = np.linalg.norm(vector_a)
    norm_b = np.linalg.norm(vector_b)
    return dot_product / (norm_a * norm_b) if norm_a and norm_b else 0.0

if __name__ == '__main__':
    if len(sys.argv) < 3:
        sys.stderr.write("Error: Faltan argumentos (texto_usuario y vector_contexto_C0).\n")
        sys.exit(1)

    user_text_input = sys.argv[1]
    context_vector_input_json = sys.argv[2] 

    try:
        user_embedding = model.encode([user_text_input])[0]
        user_embedding_list = user_embedding.tolist()

        context_vector = np.array(json.loads(context_vector_input_json))
        w_uc = calcular_similitud_coseno(user_embedding, context_vector)

        resultado = {
            'U0_vector': user_embedding_list,
            'WUC_peso': float(w_uc)
        }

        print(json.dumps(resultado))

    except Exception as e:
        sys.stderr.write(f"Error en procesamiento Python: {e}\n")
        sys.exit(1)