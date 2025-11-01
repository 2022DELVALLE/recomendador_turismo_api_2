import sys
import json
from sentence_transformers import SentenceTransformer

# Carga el modelo (¡Asegúrate que el modelo ya esté descargado en el entorno 'tarma_ai'!)
try:
    # Usa el mismo modelo que para el embedding del usuario
    model = SentenceTransformer('all-MiniLM-L6-v2') 
except Exception as e:
    # Si falla, imprimimos el error para que PHP lo capture
    sys.stderr.write(f"Error al cargar el modelo: {e}\n")
    sys.exit(1)

def vectorizar_contexto(texto_contexto):
    """Genera el vector C0 a partir del texto de contexto."""
    try:
        # Genera el embedding
        vector = model.encode(texto_contexto).tolist()
        
        # Devuelve el resultado como JSON
        resultado = {
            "C0_vector": vector
        }
        print(json.dumps(resultado))
        
    except Exception as e:
        sys.stderr.write(f"Error en la vectorización: {e}\n")
        sys.exit(1)

if __name__ == "__main__":
    # El script espera un argumento: el texto de contexto
    if len(sys.argv) < 2:
        sys.stderr.write("Uso: python vectorizar_contexto.py <texto_contexto>\n")
        sys.exit(1)

    texto_contexto = sys.argv[1]
    vectorizar_contexto(texto_contexto)