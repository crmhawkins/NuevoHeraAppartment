# Importaciones extra
import json
import os
import time

# Automatizacion Web
import requests

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
#from bs4 import BeautifulSoup


def main():

    username = os.getlogin()
    perfil_chrome = os.path.join(r"C:\Users", username, r"AppData\Local\Google\Chrome\User Data")

    options = webdriver.ChromeOptions()
    options.add_argument(f"--user-data-dir={perfil_chrome}")

    driver = webdriver.Chrome(options=options)
    driver.get("https://www.airbnb.es/hosting/reservations")

    try:        
        datos = WebDriverWait(driver, 30).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "_pmj9iv7"))
        )

        masdetalles = WebDriverWait(driver, 30).until(
            EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[aria-label="Más opciones"]'))
        )
            
        arrayData = []
        counter = 0
        x = 0


            # For - Array Multidimensional para sacar los datos


        for i in range(int(len(datos) / 8)):
            temp = []
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1
            temp.append(datos[counter].text)
            counter += 1

            arrayData.append(temp)

        for i in range(int(len(datos)/8)):
            boton_masdetalles = masdetalles[x]
            boton_masdetalles.click()
            x = x + 1

            atributo = WebDriverWait(driver, 30).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, '[aria-describedby="phone-row-subtitle"]'))
            )
            enlace = atributo.get_attribute("href")
            numero_telefono = enlace.replace("tel:", "").strip()
            try:
                # Ejemplo de fecha en el formato "5 de abr. de 2024"
                fecha_ejemplo = arrayData[i][2]
                fecha_ejemplo_salida = arrayData[i][3]
                
                # Convertir la fecha a un objeto datetime
                fecha_objeto = datetime.strptime(fecha_ejemplo, "%d de %b de %Y")
                fecha_objeto_salida = datetime.strptime(fecha_ejemplo_salida, "%d de %b de %Y")
                
                # Formatear la fecha al formato deseado
                fecha_formateada_entrada = fecha_objeto.strftime("%Y-%m-%d")
                fecha_formateada_salida = fecha_objeto_salida.strftime("%Y-%m-%d")
                print(fecha_formateada_salida)
            except ValueError as e:
                print(f"Error al analizar la fecha: {e}")
            
            print(fecha_formateada_salida)
            print(fecha_formateada_entrada)
            update = {
                
                "origen": 'Airbnb',
                "alias": arrayData[i][1],
                "telefono": numero_telefono,
                "idiomas": None,
                "fecha_entrada": fecha_formateada_entrada,
                "fecha_salida": fecha_formateada_salida,
                "fecha_reserva": arrayData[i][4],
                "apartamento": arrayData[i][5],
                "codigo_reserva": arrayData[i][6],
                "precio": arrayData[i][7],
                "email": None,
                "status": arrayData[i][0],
                "numero_personas": None

            }
            print(update)

            click_exit = WebDriverWait(driver, 30).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, '[data-testid="modal-container"]'))
            )
            click_exit.click()


            # Request URL


            url_get = f'https://crm.apartamentosalgeciras.com/comprobar-reserva/{arrayData[i][6]}'
            url_post = 'https://crm.apartamentosalgeciras.com/agregar-reserva'
            url_canceled = f'https://www.airbnb.es/hosting/reservations/canceled?confirmationCode={arrayData[i][6]}'

            # driver.execute_script("window.open('about:blank', '_blank');")
            # driver.switch_to.window(driver.window_handles[1])
            # driver.get(url_canceled)

            # comprobar_reserva = WebDriverWait(driver, 30).until(
            #     EC.presence_of_element_located((By.CSS_SELECTOR, 'span[style="color: rgb(113, 113, 113);"]'))
            # )

            response = requests.get(url_get)
            headers = {'Content-Type': 'application/json'}
            payload_json = json.dumps(update)
            print(response.status_code)
            if response.status_code == 200:    
                print(f"\n > La reserva {arrayData[i][6]} ya esta añadida")

            else:
                    reponsePost = requests.post(url_post, data=payload_json, headers=headers)
                    print(f"\n > Reserva {arrayData[i][6]} añadida")   
                    print(reponsePost)                 
    except:
        print(f"\n > No se ha podido acceder a la web de reservas, compruebe si está logueado en Airbnb.")
        input()

if __name__ == "__main__":
    main()